<?php
namespace Plugin\ImportExport;


class Service
{

    private $menusForImporting = Array(),
        $languagesForImporting = Array(),
        $archiveDir = '';

    public function startImport($uploadedFile)
    {


        Log::addRecord('Starting importing the site. ' . basename($uploadedFile), 'info');

        $extractedDirName = Zip::extractZip($uploadedFile);

        $this->archiveDir = $extractedDirName;

        $this->importSiteTree($extractedDirName);


        try {

            foreach ($this->menusForImporting as $menuItem) {

                $menuName = $menuItem['nameForImporting'];
                $menuLanguageCode = $menuItem['languageCode'];
                $recursive = true;
                Log::addRecord('Menu '.$menuName.' language: ' . $menuLanguageCode, 'info');
                $menu = \Ip\Internal\Pages\Service::getMenu($menuLanguageCode , $menuName);
                $parentSubPageId = $menu['id'];


                $directory = ipFile(
                    'file/secure/tmp/' . $extractedDirName . '/archive/' . $menuLanguageCode . '_' . $menuItem['id']
                );

                if (is_dir($directory)) {

                    Log::addRecord("Processing:" . $directory);

                    if ($language = ipContent()->getLanguageByCode($menuLanguageCode)){
                        $this->addPages($directory, $parentSubPageId, $recursive, $menuName, $language);
                    }else{
                        Log::addRecord('Language ' . $menuLanguageCode. ' not found', 'error');
                    }



                }
            }


        } catch (\Exception $e) {
            Log::addRecord("Skipping:" . $e);
        }

        Log::addRecord('Finished importing', 'success');
        return true;
    }

    private function importSiteTree($extractedDirName)
    {

        $this->menusForImporting = Array();
        $this->languagesForImporting = Array();

        $string = file_get_contents(ipFile('file/secure/tmp/' . $extractedDirName . '/archive/info.json'));
        $siteData = json_decode($string, true);

        $version = $siteData['version'];

        Log::addRecord('Importing version ' . $version, 'info');

        $this->importLanguages($siteData['languages']);

        $this->importMenus($siteData['menuLists']);

        return true;
    }

    private function importLanguages($languageList)
    {

        foreach ($languageList as $language) {

            if (!ipContent()->getLanguageByCode($language['code'])) {

                if (isset($language['urlPath'])){
                    $url = $language['urlPath'];
                }else{
                    $url = 'en';
                }

                $languageId = ipContent()->addLanguage($language['d_long'], $language['d_short'], $language['code'], $url, true);

//                \Ip\Module\Pages\Service::addLanguage($language['code'], $language['url'], $language['d_long'], $language['d_short'], false);

            } else {
                $languageId = ipContent()->getLanguageByCode($language['code'])->getId();

            }
            //TODO


            $this->languagesForImporting[] = ipContent()->getLanguage($languageId);;
        }

        return true;
    }

    private function importMenus($menuList)
    {

        foreach ($menuList as $menu) {

            $suffix = ''; // TODO Add a prefix if page with specific name already exists
//            while (ipContent()->getPage($prefix . $curZoneName . $suffix)) {
//                $suffix = $suffix + 1;
//            }

            $menuName = $menu['name'] . $suffix;
            $menuTitle = $menu['title'];
            $menuDescription = $menu['description'];
            $menuUrl = $menu['urlPath'];
            if (isset($menu['languageCode'])){
                $languageCode = $menu['languageCode'];
            }else{
                $languageCode = 'en';
            }

            $associatedModule = 'Content';
            $defaultLayout = 'main.php'; // TODO custom default layout

            $this->menusForImporting[] = Array(
                'id' =>$menu['id'],
                'nameInFile' => $menu['name'],
                'nameForImporting' => $menuName,
                'title' => $menuTitle,
                'description' => $menuDescription,
                'url' => $menuUrl,
                'associatedModule' => $associatedModule,
                'layout' => $defaultLayout,
                'languageCode' => $languageCode,
            );

            try {

                $menuExists = \Ip\Internal\Pages\Service::getMenu($languageCode, $menuName);
                if (!isset($menuExists['isDeleted']) || ($menuExists['isDeleted'] == '1')) {
                    \Ip\Internal\Pages\Service::createMenu($languageCode, $menuName, $menuTitle);
                } else {
                    Log::addRecord('Menu ' . $menuName . ' already exists. Importing anyway.', 'error');
                }

            } catch (\Exception $e) {
                throw new \Exception($e);
            }

        }


        return true;

    }

    private function addPage($parentId, $language, $pageData)
    {
        $pageSettings = $pageData['settings'];
//                            $pageTitle = $pageSettings['title'];
        $url = $pageSettings['urlPath'];


        if ($pageSettings['title']) {
            $pageTitle = $pageSettings['title'];
        } else {
            $pageTitle = '';
        }

//                            $pageId = \Ip\Module\Content\Service::addPage(
//                                $zoneName,
//                                $parentId,
//                                $buttonTitle,
//                                $pageTitle,
//                                $url
//                            );

        $pageData = array('languageCode' => $language->getCode(),
            'urlPath' => esc($url),
            'metaTitle' => esc($pageTitle),
        );


        $pageId = ipContent()->addPage($parentId, $pageTitle, $pageData);

        if (isset($pageSettings['layout'])){
            $layout = $pageSettings['layout'];
        }else{
            $layout = 'main.php';
        }

        ipPageStorage($pageId)->set('layout', $layout);

        return $pageId;
    }

    private function addPages($directory, $parentId, $recursive, $menuName, $language)
    {

        $array_items = array();


        if ($handle = opendir($directory)) {

            $dirArray = array();

            while ($file = readdir($handle)) {
                $dirArray[] = $file;
            }

            sort($dirArray);

            foreach ($dirArray as $file) {
                if ($file != "." && $file != "..") {
                    if (is_dir($directory . "/" . $file)) {

                        if ($recursive) {
                            $pageFileNamePath = $directory . "/" . $file . ".json";
                            if (is_file($pageFileNamePath)) {

                                $string = file_get_contents($pageFileNamePath);
                                $pageData = json_decode($string, true);
                                $pageId = $this->addPage($parentId, $language, $pageData);
                                $this->importWidgets($pageFileNamePath, $pageId, $menuName, $language);
                                $this->addPages($directory . "/" . $file, $pageId, true, $menuName, $language);

                            } else {
                                Log::addRecord('File ' . $pageFileNamePath . " does not exist. Menu name: " . $menuName, 'error');
                            }
                        }

                    } else {
                        $fileFullPath = $directory . "/" . $file;
                        $fileFullPathWoExt = preg_replace("/\\.[^.\\s]{3,4}$/", "", $fileFullPath);
                        if (!is_dir($fileFullPathWoExt)) {

                            $string = file_get_contents($fileFullPath);
                            $pageData = json_decode($string, true);
                            $pageId = $this->addPage($parentId, $language, $pageData);
                            $this->importWidgets($fileFullPath, $pageId, $menuName, $language);

                        }
                    }
                }
            }
            closedir($handle);
        }
        return $array_items;
    }

    private function importWidgets($fileName, $pageId, $menuName, $language)
    {

        $pageRevision = \Ip\Internal\Revision::getLastRevision($pageId);
        $revisionId = $pageRevision['revisionId'];

        $languageId = $language->getId();
        $languageDir = $language->getUrlPath();

        // $this->addLogRecord('Importing widgets from '.$fileName, 'info');

        $buttonTitle = basename($fileName, ".json");
        $url = $buttonTitle;


        $string = file_get_contents($fileName);

        $position = 0;

        $pageData = json_decode($string, true);


        if (isset($pageData['widgets'])) {

            $widgetList = $pageData['widgets'];

            foreach ($widgetList as $widgetKey => $widgetValue) {

                if (isset($widgetValue['blockName'])){
                    $blockName = $widgetValue['blockName'];
                }else{
                    $blockName = 'main'; // TODO X Allow to import all blocks, not only main
                }

                if (isset($widgetValue['type'])) {
                    $widgetName = $widgetValue['type'];

                    //TODO Testing
                    $processWidget = false;

                    if (isset($widgetValue['layout'])) {
                        $layout = $widgetValue['layout'];
                    } else {
                        $layout = 'default';
                    }

                    if (isset($widgetValue['data'])) {
                        $widgetData = $widgetValue['data'];
                    } else {
                        $widgetData = null;
                    }

                    $content = array();

                    switch ($widgetName) {
                        case 'Divider':
                            $content = null;
                            $processWidget = true;
                            break;
                        case 'Image':
                            if (isset($widgetData['imageOriginal'])) {

                                // Create a file with a  new name if a file already exists

                                $newFileName = self::createFileName($widgetData['imageOriginal']);

                                $this->copyFileToRepository($widgetData['imageOriginal'], $newFileName);

                                $content['imageOriginal'] = $newFileName;

                                if (isset($widgetData['cropX1'])) {
                                    $content['cropX1'] = $widgetData['cropX1'];
                                }

                                if (isset($widgetData['cropX2'])) {
                                    $content['cropX2'] = $widgetData['cropX2'];
                                }

                                if (isset($widgetData['cropY1'])) {
                                    $content['cropY1'] = $widgetData['cropY1'];
                                }

                                if (isset($widgetData['cropY2'])) {
                                    $content['cropY2'] = $widgetData['cropY2'];
                                }

                                $processWidget = true;
                            }

                            break;
                        case 'Text':
                            $content['text'] = $widgetData['text'];
                            $processWidget = true;
                            break;

                        case 'File':

                            foreach ($widgetData['files'] as $file) {

                                $newFile = array();

                                if (isset($file['fileName'])) {

                                    // Create a file with a  new name if a file already exists

                                    $newFileName = $this->createFileName($file['fileName']);


                                    $this->copyFileToRepository($file['fileName'], $newFileName);

                                    $newFile['fileName'] = $newFileName;

                                    if (isset($file['title'])) {
                                        $newFile['title'] = $file['title'];
                                    }

                                }
                                $content['files'][] = $newFile;
                            }

                            $processWidget = true;

                            break;


                        case 'Gallery':
                            if (isset($widgetData['images'])) {
                                foreach ($widgetData['images'] as $image) {

                                    $newImage = array();

                                    if (isset($image['imageOriginal'])) {

                                        // Create a file with a  new name if a file already exists

                                        $newFileName = $this->createFileName($image['imageOriginal']);

                                        $this->copyFileToRepository($image['imageOriginal'], $newFileName);

                                        $newImage['imageOriginal'] = $newFileName;

                                        if (isset($image['title'])) {
                                            $newImage['title'] = $image['title'];
                                        } else {
                                            $newImage['title'] = '';
                                        }

                                        if (isset($image['cropX1'])) {
                                            $newImage['cropX1'] = $image['cropX1'];
                                        }

                                        if (isset($image['cropX2'])) {
                                            $newImage['cropX2'] = $image['cropX2'];
                                        }

                                        if (isset($image['cropY1'])) {
                                            $newImage['cropY1'] = $image['cropY1'];
                                        }

                                        if (isset($image['cropY2'])) {
                                            $newImage['cropY2'] = $image['cropY2'];
                                        }

                                    }
                                    $content['images'][] = $newImage;

                                }

                                $processWidget = true;
                            }
                            break;

                        case 'Heading':
                            if (isset($widgetData['title'])){
                                $content['title'] = $widgetData['title'];
                                if (isset($widgetData['level'])) {
                                    $content['level'] = $widgetData['level'];
                                } else {
                                    $content['level'] = 1;
                                }

                                $processWidget = true;
                            }
                            break;
                        case 'Html':
                            $content['html'] = $widgetData['html'];
                            if (!isset($widgetValue['layout'])) {
                                $layout = 'escape'; // default layout for code examples
                            }
                            $processWidget = true;
                            break;

                        case 'FileDoc':
                            $content = $widgetData;
                            $processWidget = true;
                            break;

                        case 'CodeHighlight':
                            $content = $widgetData;
                            $processWidget = true;
                            break;

                        default:
                            $content = null;
                            break;
                    }

                    if ($processWidget) {
                        $position++;

                        if (isset($widgetValue['layout'])) {
                            $skin = $widgetValue['layout'];
                        } else {
                            $skin = 'default';
                        }

                        if (!isset($widgetData)) {
                            $content = array();
                        }

                        $widgetId = \Ip\Internal\Content\Service::createWidget($widgetName, $content, $skin, $revisionId, 0, $blockName, $position);


//                        \Ip\Internal\Content\Service::addWidgetInstance($widgetId, $revisionId, 0, $blockName, $position);

//                        $instanceId = \Ip\Internal\Content\Service::addWidget(
//                            $widgetName,
//                            $zoneName,
//                            $pageId,
//                            $blockName,
//                            $revisionId,
//                            $position
//                        );
//


                        // \Ip\Internal\Revision::getLastRevision($pageId)
                        // createWidget
                        // addWidgetInstance($widgetId, $revisionId, $languageId, $block, $position, $visible = true)
//                        \Ip\Module\Content\Service::addWidgetContent($instanceId, $content, $layout);
//                        $this->addLogRecord('Widget ' . $widgetName . " added. File name: ".$fileName.", Menu name: ".$menuName. ", Language: ".$languageDir, 'danger');
                    } else {
                        Log::addRecord('ERROR: Widget ' . $widgetName . " not supported or has bad parameters. File name: " . $fileName . ", Zone name: " . $menuName . ", Language: " . $languageDir, 'danger');
                    }
                }

            }


        }
    }

    public static function createFileName($fullPathName)
    {

        $path = pathinfo($fullPathName, PATHINFO_DIRNAME);
        $ext = pathinfo($fullPathName, PATHINFO_EXTENSION);
        $filenameWoExt = basename($fullPathName, "." . $ext);

        $cnt = 0;

        $newFilename = $filenameWoExt . "." . $ext;

        while (file_exists(ipFile($path . '/' . $newFilename))) {

            $newFilename = $filenameWoExt . '_' . $cnt . "." . $ext;
            $cnt++;
        }

        return $newFilename;
    }

    private function copyFileToRepository($fileNameInArchive, $newFileName)
    {

        $fileFromArchive = ipFile('file/secure/tmp/' . $this->archiveDir . '/archive/file/' . $fileNameInArchive);

        $newFileNamePath = ipFile('file/repository/' . $newFileName);

        if (file_exists($fileFromArchive)) {
            if (!copy($fileFromArchive, $newFileNamePath)){
                Log::addRecord('Failed to copy file ' . $fileFromArchive.' to '.$newFileNamePath, 'warning');
            }
        } else {
            Log::addRecord('File does not exist ' . $fileFromArchive, 'warning');
            //TODOX throw exception
        }

    }

    public function startExport()
    {
        try {
            $response['results'] = ManagerExport::exportSiteTree();
            $response['status'] = "success";
        } catch (\Exception $e) {
            Log::addRecord($e);
            $response['results'] = Log::getLog();
            $response['status'] = "error";
        }
        return $response;
    }


}