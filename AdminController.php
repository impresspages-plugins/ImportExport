<?php
namespace Plugin\importExport;


class AdminController extends \Ip\Controller
{
    public function index()
    {
        $this->extractZip();
        $this->importZones();

        $zones = ipContent()->getZones();

        $parentId=0;
        $recursive = true;


print "ZONES IMPORTED";

        $zoneData['id'] = 111;
        $zoneData['name'] = 'TestName';
        $zoneData['template'] = 'testTemplate';
        $zoneData['title'] = 'Title';
        $zoneData['url'] = 'URL';
        $zoneData['keywords'] = '';
        $zoneData['description'] = 'Test description';

        ipAddPluginAsset('ImportExport', 'importExport.js');
        try {



            foreach ($zones as $zone){


                $zoneName = $zone->getName();

                print "PROCESSING ZONE:".$zoneName;

//                $zone = ipContent()->getZone($zoneName);
                $zoneId = $zone->getId();

                $recursive = true; //TODO




                $languages = \Ip\Module\Pages\Db::getLanguages();

                foreach ($languages as $key => $language) {
                    print_r($language);

                    $language_id = $language['id'];

                    $directory = ipConfig()->fileDirFile('ImportExport/archive/'.$language['url'].'_'.$language_id.'/'.$zoneName);

                    if(file_exists($directory) || is_dir($directory)){
                        echo "<br>Processing:".$directory;
                        $parentPageId = \Ip\Module\Pages\Db::rootContentElement($zone->getId(), $language_id);

                        $this->addZonePages($directory, $parentPageId, $recursive, $zoneName, $language);



                    }else{
                        echo "<br>Skipping:".$directory;
                    }
                }
            }
        } catch (\Exception $e) {
            echo $e;
        }

        return 'test';
    }


    private function importZones()
    {

        $string = file_get_contents( ipConfig()->fileDirFile('ImportExport/archive/zones.json'));
        $json_a = json_decode($string, true);
        foreach ($json_a as $zone) {

            $zoneName = $zone['name'];
            $zoneTitle = $zone['title'];
            $zoneDescription = $zone['description'];
            $zoneUrl = $zone['url'];
            $associatedModule = 'Content';
            $defaultLayout = 'main.php';

            \Ip\Module\Pages\Service::addZone($zoneTitle, $zoneName, $associatedModule, $defaultLayout, null, $zoneDescription, $zoneUrl );


        }
        return true;
    }

    private function extractZip()
    {

        try {
            $zipLib = ipConfig()->pluginFile('ImportExport/lib/pclzip.lib.php');
            require_once($zipLib);

            $archive = new \PclZip(ipConfig()->fileDirFile('ImportExport/archive.zip'));

            if ($archive->extract(PCLZIP_OPT_PATH, ipConfig()->fileDirFile('ImportExport')) == 0) {
                die("Error : " . $archive->errorInfo(true));
            }

        } catch (\Exception $e) {
            echo $e;
        }
    }

    private function importWidgets($fileName, $pageId, $zoneName, $language){

        $language_id = $language['id'];
        $languageDir = $language['url'];


        $zone = ipContent()->getZone($zoneName);

        $parentPageId = \Ip\Module\Pages\Db::rootContentElement($zone->getId(), $language_id);


        if ($parentPageId === false) {
            trigger_error("Can't find root zone element.");

            return false;
        }

        $parentPage = $zone->getPage($parentPageId);

        $path = realpath(ipConfig()->fileDirFile('ImportExport') ).'/archive/'.$languageDir.'_'.$language_id.'/'.$zoneName;


            //TODO get page data from JSON
            $buttonTitle = basename($fileName, ".json");
            $url = $buttonTitle;

            print "Button title:".$buttonTitle;

            $revisionId = \Ip\Revision::createRevision($zoneName, $pageId, true);



            $string = file_get_contents( $fileName);

            $position = 0;

            $widgetData = json_decode($string, true);

            foreach ($widgetData as $widgetKey=>$widgetValue){
                if (isset($widgetValue['type'])){
                    $widgetName = $widgetValue['type'];

                    //TODO Testing
                    $processWidget = false;

                    switch ($widgetName){
                        case 'IpSeparator':
                            $content = null;
                            $processWidget = true;
                            break;
                        case 'IpTable':
                            $content['text'] = $widgetValue['text'];
                            $processWidget = true;
                            break;
                        case 'IpText':
                            $content['text'] = $widgetValue['text'];
                            $processWidget = true;
                        break;
                        case 'IpTextImage': // Import IpTextImage as IpText
                            $widgetName = 'IpText';
                            $content['text'] = $widgetValue['text'];
                            $processWidget = true;
                            break;
                        case 'IpTitle':
                        $content['title'] = $widgetValue['title'];
                        $processWidget = true;
                        break;
                        case 'IpHtml':
                            $content['html'] = $widgetValue['html'];
                            $processWidget = true;
                            break;
                        default:
                            $content = null;
                        break;
                    }

                    if ($processWidget){
                        $position++;
                        $instanceId = \Ip\Module\Content\Service::addWidget(
                            $widgetName,
                            $zoneName,
                            $pageId,
                            'main',
                            $revisionId,
                            $position
                        );

                        \Ip\Module\Content\Service::addWidgetContent($instanceId, $content, $layout = 'default');


                    }else{
                        echo '<br>ERR:'.$widgetName." not supported<br>";
                    }
                }




        }
    }

    private function addPages($parentId, $pages, $zoneName, $language){
        foreach ($pages as $fileName){
            $buttonTitle = basename($fileName, ".json");
            $url = $buttonTitle;
            $pageId = \Ip\Module\Content\Service::addPage($zoneName, $parentId, $buttonTitle, $buttonTitle, $url);


            if (is_file($fileName)){
                $this->importWidgets($fileName, $pageId, $zoneName, $language);
            }

        }
        return true;
    }


    private function addZonePages($directory, $parentId, $recursive, $zoneName, $language) {
        $array_items = array();
        print "adding zone pages";
        if ($handle = opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir($directory. "/" . $file)) {
                        if($recursive) {
                            print "<Br>PARENT ID:".$parentId;
                            print "INSERT ";
                            print "<br><br>";

                            $buttonTitle = basename($file);
                            $url = $buttonTitle;
                            print "PARENT ID::::".$parentId;


                            $pageId = \Ip\Module\Content\Service::addPage($zoneName, $parentId, $buttonTitle, $buttonTitle, $url);
                            print "ADDED PAGE FOR TREE:::".$file.":::".$pageId."<Br>";
                            $this->addZonePages($directory. "/" . $file, $pageId, $recursive, $zoneName, $language);
                        }
                        $file = $directory . "/" . $file;
                    } else {
                        $file = $directory . "/" . $file;

                        $buttonTitle = basename($file, ".json");
                        $url = $buttonTitle;
                        $pageId = \Ip\Module\Content\Service::addPage($zoneName, $parentId, $buttonTitle, $buttonTitle, $url);
                        print "ADDED PAGE:::".$file.":::".$pageId."<Br>";
                        $this->importWidgets($file, $pageId, $zoneName, $language);
                        print "ADDED WIDGETS<Br>";
                    }
                }
            }
            closedir($handle);
        }
        return $array_items;
    }


}