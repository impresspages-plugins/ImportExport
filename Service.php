<?php
namespace Plugin\ImportExport;


class Service
{

    private $zonesForImporting = Array(),
        $languagesForImporting = Array(),
        $importLog = Array();

    public function startImport($uploadedFile)
    {


        $this->addLogRecord('Starting importing the site. '.$uploadedFile->getOriginalFileName(), 'info');

        $extractedDirName = $this->extractZip($uploadedFile);
        $this->importSiteTree($extractedDirName);

        $parentId = 0;
        $recursive = true;

        foreach ($this->languagesForImporting as $language) { // TODO X fix languages

        }

        $languageCode = $language->getCode(); // TODO X fix languages

        try {

            foreach ($this->zonesForImporting as $zone) {

                $zoneName = $zone['nameForImporting'];
                $this->addLogRecord('ZONE NAME: ' . $zoneName, 'info');
                $recursive = true;
                $this->addLogRecord('Processing language: ' . $language->getCode(), 'info');
                $menu = \Ip\Internal\Pages\Service::getMenu($languageCode, $zoneName);
                $parentSubPageId = $menu['id'];

                $pageData = array('languageCode' =>  $language->getCode());

                $directory = ipFile(
                    'file/secure/tmp/' . $extractedDirName .'/archive/'. $language->getUrl() . '_' . $zone['nameInFile']
                );

                if (is_dir($directory)) {

                    $this->addLogRecord("Processing:" . $directory);

                    $this->addZonePages($directory, $parentSubPageId, $recursive, $zoneName, $language);

                }
            }


        } catch (\Exception $e) {
            $this->addLogRecord("Skipping:" . $e);
        }

        $this->addLogRecord('Finished importing', 'success');
        return true;
    }

    private function importSiteTree($extractedDirName)
    {

        $this->zonesForImporting = Array();
        $this->languagesForImporting = Array();

        $string = file_get_contents(ipFile('file/secure/tmp/' . $extractedDirName . '/archive/info.json'));
        $siteData = json_decode($string, true);

        $version = $siteData['version'];

        $this->addLogRecord('Importing version '.$version, 'info');

        $this->importLanguages($siteData['languages']);

        $this->importZones($siteData['zones']);

        return true;
    }

    private function importZones($zoneList){

        foreach ($zoneList as $zone) {

            $curZoneName = $zone['name'];
            $prefix = 'imported_';
            $suffix = ''; // TODO Add a prefix if page with specific name already exists
//            while (ipContent()->getZone($prefix . $curZoneName . $suffix)) {
//                $suffix = $suffix + 1;
//            }

            $zoneName = $prefix . $zone['name'] . $suffix;
            $zoneTitle = $zone['title'];
            $zoneDescription = $zone['description'];
            $zoneUrl = $zone['url'];
            $associatedModule = 'Content';
            $defaultLayout = 'main.php';

            $this->zonesForImporting[] = Array(
                'nameInFile' => $zone['name'],
                'nameForImporting' => $zoneName,
                'title' => $zoneTitle,
                'description' => $zoneDescription,
                'url' => $zoneUrl,
                'associatedModule' => $associatedModule,
                'layout' => $defaultLayout
            );

            try {
//                \Ip\Module\Pages\Service::addZone(
//                        $zoneTitle,
//                        $zoneName,
//                        $associatedModule,
//                        $defaultLayout,
//                        null,
//                        $zoneDescription,
//                    $zoneUrl

                $menuExists = \Ip\Internal\Pages\Service::getMenu('en', $zoneName);
                if (!isset($menuExists['isDeleted']) || ($menuExists['isDeleted'] == '0')){
                    \Ip\Internal\Pages\Service::createMenu('en', $zoneName, $zoneName);
                }

            } catch (\Exception $e) {
                throw new \Exception($e);
            }

        }


        return true;

    }

    private function importLanguages($languageList)
    {

        foreach ($languageList as $language){
            if (!Model::languageExists($language['url'])){

                $languageId = ipContent()->addLanguage($language['d_long'], $language['d_short'], $language['code'], $language['url'], true);

//                \Ip\Module\Pages\Service::addLanguage($language['code'], $language['url'], $language['d_long'], $language['d_short'], false);

            }else{
                $languageId = Model::getLanguageIdByUrl($language['url']);
            }
            //TODO



            $this->languagesForImporting[] = ipContent()->getLanguage($languageId);;
        }

        return true;
    }

    private function extractZip($file)
    {
        $extractSubDir = false;

        $fileName = $file->getOriginalFileName();

        try {
            $zipLib = ipFile('Plugin/ImportExport/lib/pclzip.lib.php');
            require_once($zipLib);

            $archive = new \PclZip(ipFile('file/secure/tmp/' . $fileName));

            $zipNameNoExt = basename($fileName, '.zip');
            $extractSubDir = $zipNameNoExt;
            $count = 0;
            while (is_file(ipFile('file/secure/tmp/' . $extractSubDir)) || is_dir(
                    ipFile('file/secure/tmp/' . $extractSubDir)
                )) {
                $count++;
                $extractSubDir = $zipNameNoExt . '_' . $count;
            }

            if ($archive->extract(
                    PCLZIP_OPT_PATH,
                    ipFile('file/secure/tmp'),
                    PCLZIP_OPT_ADD_PATH,
                    $extractSubDir
                ) == 0
            ) {
                die("Error : " . $archive->errorInfo(true));
            }
        } catch (\Exception $e) {
            $this->addLogRecord($e);
        }
        return $extractSubDir;
    }

    private function importWidgets($fileName, $pageId, $zoneName, $language)
    {

        $pageRevision = \Ip\Internal\Revision::getLastRevision($pageId);
        $revisionId = $pageRevision['revisionId'];

        $languageId = $language->getId();
        $languageDir = $language->getUrl();

        $this->addLogRecord('Importing widgets from '.$fileName, 'info');

        $buttonTitle = basename($fileName, ".json");
        $url = $buttonTitle;

        $string = file_get_contents($fileName);

        $position = 0;

        $pageData = json_decode($string, true);

        if (isset($pageData['widgets'])) {

            $widgetList = $pageData['widgets'];

            foreach ($widgetList as $widgetKey => $widgetValue) {

                $blockId = 'main';

                if (isset($widgetValue['type'])) {
                    $widgetName = $widgetValue['type'];

                    //TODO Testing
                    $processWidget = false;

                    if (isset($widgetValue['layout'])){
                        $layout =  $widgetValue['layout'];
                    }else{
                        $layout =  'default';
                    }

                    if (isset($widgetValue['data'])){

                        $widgetData = $widgetValue['data'];

                    }

                    switch ($widgetName) {
                        case 'Separator':
                            $content = null;
                            $processWidget = true;
                            break;
                        case 'Table':
                            $content['text'] = $widgetData['text'];
                            $processWidget = true;
                            break;
                        case 'Text':
                            $content['text'] = $widgetData['text'];
                            $processWidget = true;
                            break;
                        case 'TextImage': //  IpTextImage as IpText
                            $widgetName = 'IpText';
                            $content['text'] = $widgetData['text'];
                            $processWidget = true;
                            break;
                        case 'Title':
                            $content['title'] = $widgetData['title'];
                            $processWidget = true;
                            break;
                        case 'Html':
                            $content['html'] = $widgetData['html'];
                            if (!isset($widgetValue['layout'])) {
                                $layout = 'escape'; // default layout for code examples
                            }

                            $processWidget = true;
                            break;
                        default:
                            $content = null;
                            break;
                    }

                    if ($processWidget) {
                        $position++;

                        $widgetId = \Ip\Internal\Content\Service::createWidget($widgetName, $widgetData);
                        \Ip\Internal\Content\Service::addWidgetInstance($widgetId, $revisionId, 0, $blockId, $position);

//                        $instanceId = \Ip\Internal\Content\Service::addWidget(
//                            $widgetName,
//                            $zoneName,
//                            $pageId,
//                            $blockId,
//                            $revisionId,
//                            $position
//                        );
//



                        // \Ip\Internal\Revision::getLastRevision($pageId)
                        // createWidget
                        // addWidgetInstance($widgetId, $revisionId, $languageId, $block, $position, $visible = true)
//                        \Ip\Module\Content\Service::addWidgetContent($instanceId, $content, $layout);
                        $this->addLogRecord('Widget ' . $widgetName . " added. File name: ".$fileName.", Menu name: ".$zoneName. ", Language: ".$languageDir, 'danger');
                    } else {
                        $this->addLogRecord('ERROR: Widget ' . $widgetName . " not supported. File name: ".$fileName.", Zone name: ".$zoneName. ", Language: ".$languageDir, 'danger');
                    }
                }

            }


        }
    }


    private function addZonePages($directory, $parentId, $recursive, $zoneName, $language)
    {



        $array_items = array();


        if ($handle = opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir($directory . "/" . $file)) {

                        if ($recursive) {
                            $pageFileNamePath = $directory . "/" . $file . ".json";
                            if (is_file($pageFileNamePath)) {
                                $string = file_get_contents($pageFileNamePath);
                                $pageData = json_decode($string, true);

                                $pageSettings = $pageData['settings'];
                                $buttonTitle = $pageSettings['button_title'];
                                $pageTitle = $pageSettings['page_title'];
                                $url = $pageSettings['url'];

//                                $pageId = \Ip\Module\Content\Service::addPage(
//                                    $zoneName,
//                                    $parentId,
//                                    $buttonTitle,
//                                    $pageTitle,
//                                    $url
//                                );
                                $pageData = array('languageCode' =>  $language->getCode());


                                $pageId = ipContent()->addPage($parentId, $pageTitle, $pageData);

                                $this->addZonePages($directory . "/" . $file, $pageId, $recursive, $zoneName, $language);
                            }else{
                                $this->addLogRecord('ERROR: File ' . $pageFileNamePath . " does not exist. Zone name: ".$zoneName);
                            }
                        }

                    } else {
                        $fileFullPath = $directory . "/" . $file;
                        if (!is_dir(preg_replace("/\\.[^.\\s]{3,4}$/", "", $fileFullPath))) {
                            $string = file_get_contents($fileFullPath);

                            $pageData = json_decode($string, true);
                            $pageSettings = $pageData['settings'];
                            $buttonTitle = $pageSettings['button_title'];
                            $pageTitle = $pageSettings['page_title'];
                            $url = $pageSettings['url'];


//                            $pageId = \Ip\Module\Content\Service::addPage(
//                                $zoneName,
//                                $parentId,
//                                $buttonTitle,
//                                $pageTitle,
//                                $url
//                            );

                            $pageData = array('languageCode' =>  $language->getCode(),
                                                'urlCode' =>  $language->getUrl());

                            $pageId = ipContent()->addPage($parentId, $pageTitle, $pageData);

                             $this->importWidgets($fileFullPath, $pageId, $zoneName, $language);


                        }
                    }
                }
            }
            closedir($handle);
        }
        return $array_items;
    }

    private function addLogRecord($msg, $status = 'warning')
    {
        $this->importLog[] = Array('message' => $msg, 'status' => $status);

    }

    public function getImportLog()
    {
        return $this->importLog;
    }
}