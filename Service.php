<?php
namespace Plugin\ImportExport;

use Ip\Module\Languages\Db;

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


        $zones = ipContent()->getZones();

        $parentId = 0;
        $recursive = true;

        try {

            foreach ($this->zonesForImporting as $zone) {

                $zoneName = $zone['nameForImporting'];

                $this->addLogRecord('ZONE NAME: ' . $zoneName, 'info');

                $zoneObj = ipContent()->getZone($zoneName);

                $zoneId = $zoneObj->getId();

                $recursive = true;

                $languages = \Ip\Module\Pages\Db::getLanguages();



                foreach ($this->languagesForImporting as $language) {

//                    $this->addLogRecord('Processing language: ' . $language['url'], 'info');


                    $language_id = $language['id'];



                    $directory = ipFile(
                        'file/secure/tmp/' . $extractedDirName .'/archive/'. $language['url'] . '_' . $zone['nameInFile']
                    );



                    if (is_dir($directory)) {

//                        $this->addLogRecord("Processing:" . $directory);

                        $parentPageId = \Ip\Module\Pages\Db::rootContentElement($zoneId, $language_id);


                        $this->addZonePages($directory, $parentPageId, $recursive, $zoneName, $language);

                    }

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

        $this->importZones($siteData['zones']);


        $this->importLanguages($siteData['languages']);

        return true;
    }

    private function importZones($zoneList){

        foreach ($zoneList as $zone) {

            $curZoneName = $zone['name'];
            $prefix = 'imported_';
            $suffix = '';
            while (ipContent()->getZone($prefix . $curZoneName . $suffix)) {
                $suffix = $suffix + 1;
            }

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
                \Ip\Module\Pages\Service::addZone(
                    $zoneTitle,
                    $zoneName,
                    $associatedModule,
                    $defaultLayout,
                    null,
                    $zoneDescription,
                    $zoneUrl
                );
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

                \Ip\Module\Pages\Service::addLanguage($language['code'], $language['url'], $language['d_long'], $language['d_short'], false);

            }
            //TODO


            $this->languagesForImporting[] = \Ip\Module\Pages\LanguageModel::getLanguageByUrl($language['url']);
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

        $language_id = $language['id'];
        $languageDir = $language['url'];



//        $this->addLogRecord('Importing widgets from '.$fileName, 'info');

        $zone = ipContent()->getZone($zoneName);

        $parentPageId = \Ip\Module\Pages\Db::rootContentElement($zone->getId(), $language_id);


        if ($parentPageId === false) {
            trigger_error("Can't find root zone element.");

            return false;
        }

        $parentPage = $zone->getPage($parentPageId);


        //TODO get page data from JSON
        $buttonTitle = basename($fileName, ".json");
        $url = $buttonTitle;

        $revisionId = \Ip\Revision::createRevision($zoneName, $pageId, true);

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
                        case 'IpSeparator':
                            $content = null;
                            $processWidget = true;
                            break;
                        case 'IpTable':
                            $content['text'] = $widgetData['text'];
                            $processWidget = true;
                            break;
                        case 'IpText':
                            $content['text'] = $widgetData['text'];
                            $processWidget = true;
                            break;
                        case 'IpTextImage': //  IpTextImage as IpText
                            $widgetName = 'IpText';
                            $content['text'] = $widgetData['text'];
                            $processWidget = true;
                            break;
                        case 'IpTitle':
                            $content['title'] = $widgetData['title'];
                            $processWidget = true;
                            break;
                        case 'IpHtml':
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
                        $instanceId = \Ip\Module\Content\Service::addWidget(
                            $widgetName,
                            $zoneName,
                            $pageId,
                            $blockId,
                            $revisionId,
                            $position
                        );

                        \Ip\Module\Content\Service::addWidgetContent($instanceId, $content, $layout);

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

                                $pageId = \Ip\Module\Content\Service::addPage(
                                    $zoneName,
                                    $parentId,
                                    $buttonTitle,
                                    $pageTitle,
                                    $url
                                );
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


                            $pageId = \Ip\Module\Content\Service::addPage(
                                $zoneName,
                                $parentId,
                                $buttonTitle,
                                $pageTitle,
                                $url
                            );

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