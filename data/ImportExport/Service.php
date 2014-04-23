<?php
namespace Modules\data\ImportExport;

//use Ip\Module\Languages\Db;


class Service
{

    private $zonesForImporting = Array(),
        $languagesForImporting = Array(),
        $importLog = Array();

    public function startImport($uploadedFile)
    {

        global $site;

        Log::addRecord('Starting importing the site. '.$uploadedFile->getOriginalFileName(), 'info');

        $extractedDirName = Zip::extractZip($uploadedFile);
        $this->importSiteTree($extractedDirName);


        $zones = $site->getZones();

        $parentId = 0;
        $recursive = true;

        try {

            foreach ($this->zonesForImporting as $zone) {

                $zoneName = $zone['nameForImporting'];

                Log::addRecord('ZONE NAME: ' . $zoneName, 'info');

                $zoneId = ModelImport::getZoneIdByName($zoneName);

                $recursive = true;

                $languages = $site->getLanguages();

                foreach ($this->languagesForImporting as $language) {

                    $language_id = $language['id'];
                    $language_url =  $language['url'];

                    $directory = BASE_DIR.
                        'file/secure/tmp/' . $extractedDirName .'/archive/'. $language_url .
                        '_' . $zone['nameInFile'];

                    if (is_dir($directory)) {

//                        $this->addLogRecord("Processing:" . $directory);

                        $parentPageId = \Modules\standard\menu_management\Db::rootContentElement($zoneId, $language_id);
                        $this->addZonePages($directory, $parentPageId, $recursive, $zoneName, $language);

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

        $this->zonesForImporting = Array();
        $this->languagesForImporting = Array();

        $string = file_get_contents(BASE_DIR.'file/secure/tmp/' . $extractedDirName . '/archive/info.json');
        $siteData = json_decode($string, true);

        $version = $siteData['version'];

        Log::addRecord('Importing version '.$version, 'info');

        $this->importZones($siteData['menuLists']);


        $this->importLanguages($siteData['languages']);

        return true;
    }

    private function importZones($zoneList){

        global $site;

        foreach ($zoneList as $zone) {

            $curZoneName = $zone['name'];
            $prefix = 'imported_';
            $suffix = '';
            while ($site->getZone($prefix . $curZoneName . $suffix)) {
                $suffix = $suffix + 1;
            }

            $zoneName = $prefix . $zone['name'] . $suffix;
            $zoneTitle = $zone['title'];
            $zoneDescription = $zone['description'];
            $zoneUrl = $zone['url'];
            $associatedModule = 'content_management';
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
                $zoneId = ModelImport::addZone(
                    $zoneTitle,
                    $zoneName,
                    $associatedModule,
                    $defaultLayout,
                    'standard',
                    $zoneDescription,
                    $zoneUrl
                );

                global $parametersMod;
                $parametersMod = new \ParametersMod();
                \Modules\developer\zones\Db::afterInsert($zoneId);

            } catch (\Exception $e) {
                throw new \Exception($e);
            }

        }

        return true;

    }

    private function importLanguages($languageList)
    {

        global $site;

        foreach ($languageList as $language){
            if (!ModelImport::languageExists($language['url'])){

                self::addLanguage($language['code'], $language['url'], $language['d_long'], $language['d_short'], false);

            }
            //TODO


            $this->languagesForImporting[] = ModelImport::getLanguageByUrl($language['url']);
        }

        return true;
    }



    private function importWidgets($fileName, $pageId, $zoneName, $language)
    {

        global $site;

        $language_id = $language['id'];
        $languageDir = $language['url'];

//        $this->addLogRecord('Importing widgets from '.$fileName, 'info');

        $zone = $site->getZone($zoneName);

        $parentPageId = \Modules\standard\menu_management\Db::rootContentElement(ModelImport::getZoneIdByName($zoneName), $language_id);

        if ($parentPageId === false) {
            trigger_error("Can't find root zone element.");
            return false;
        }

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
                        case 'Divider':
                            $widgetName = 'IpSeparator';
                            $content = null;
                            $processWidget = true;
                            break;
                        case 'Text':
                            $widgetName = 'IpText';
                            $content['text'] = $widgetData['text'];
                            $processWidget = true;
                            break;
                        case 'Heading':
                            $widgetName = 'IpTitle';
                            $content['title'] = $widgetData['title'];
                            $processWidget = true;
                            break;
                        case 'Html':
                            $widgetName = 'IpHtml';
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
                        $instanceId = ModelImport::addWidget(
                            $widgetName,
                            $zoneName,
                            $pageId,
                            $blockId,
                            $revisionId,
                            $position
                        );

                        if (is_int($instanceId)){
                            ModelImport::addWidgetContent($instanceId, $content, $layout);
                        }else{
                            Log::addRecord('ERROR: Failed to created an instance of ' . $widgetName . ". File name: ".$fileName.", Zone name: ".$zoneName. ", Language: ".$languageDir, 'danger');
                        }

                    } else {
                        Log::addRecord('ERROR: Widget ' . $widgetName . " not supported. File name: ".$fileName.", Zone name: ".$zoneName. ", Language: ".$languageDir, 'danger');
                    }
                }

            }


        }
    }


    private function addZonePages($directory, $parentId, $recursive, $zoneName, $language)
    {

        $array_items = array();

        $position = 0;

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
                                $position++;
                                $url = $pageSettings['url'];
                                $visible = $pageSettings['visible'];


                                $pageId = ModelImport::addPage(
                                    $zoneName,
                                    $parentId,
                                    $buttonTitle,
                                    $pageTitle,
                                    $url,
                                    $position,
                                    $visible
                                );
                                $this->addZonePages($directory . "/" . $file, $pageId, $recursive, $zoneName, $language);
                            }else{
                                Log::addRecord('ERROR: File ' . $pageFileNamePath . " does not exist. Zone name: ".$zoneName);
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
                            $position++;
                            $url = $pageSettings['url'];
                            $visible = $pageSettings['visible'];

                            $pageId = ModelImport::addPage(
                                $zoneName,
                                $parentId,
                                $buttonTitle,
                                $pageTitle,
                                $url,
                                $position,
                                $visible
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



    public function addLanguage($code, $url, $d_long = '', $d_short = '', $visible = true, $text_direction='ltr'){

        if (($code!='') && ($url!='')){

            $dbh = \Ip\Db::getConnection();

            $data = Array();
            $data['code'] = $code;
            $data['url'] = $url;
            $data['d_long'] = $d_long;
            $data['d_short'] = $d_short;
            $data['visible'] = $visible;
            $data['text_direction'] = $text_direction;


            $sql = "INSERT INTO `".DB_PREF."language`
                (code, url, d_long, d_short, visible, text_direction)
                VALUES
                (:code, :url, :d_long, :d_short, :visible, :text_direction)";


            $sth = $dbh->prepare($sql);

            $sth->execute($data);


//TODO            $this->afterInsert($id);

            return true;
        }else{
            trigger_error("Can't create language. Missing URL or language code.");
        }
    }

    public function startExport(){

        try {
            $response['results'] = ManagerExport::exportSiteTree();
            $response['status'] = "success";
        }catch (\Exception $e){
            Log::addRecord($e);
            $response['results'] = $this->importLog;
            $response['status'] = "error";
        }
        return $response;
    }




}