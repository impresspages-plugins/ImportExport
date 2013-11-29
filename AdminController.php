<?php
namespace Plugin\importExport;


class AdminController extends \Ip\Controller
{
    public function index()
    {

        $this->extractZip();
        $this->importZones();



        $zoneData['id'] = 111;
        $zoneData['name'] = 'TestName';
        $zoneData['template'] = 'testTemplate';
        $zoneData['title'] = 'Title';
        $zoneData['url'] = 'URL';
        $zoneData['keywords'] = '';
        $zoneData['description'] = 'Test description';

        ipAddPluginAsset('ImportExport', 'importExport.js');


        try {

            $zones = \Ip\Module\Pages\Db::getZones();

            foreach ($zones as $zone){

                $zoneName = $zone['name'];

                $zone = ipContent()->getZone($zoneName);

                $languages = \Ip\Module\Pages\Db::getLanguages();

                foreach ($languages as $key => $language) {
                    $language_id = $language['id'];


                    $this->importWidgets($zone, $language);

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

            $zone = ipContent()->getZone($zoneName);

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

    private function importWidgets($zone, $language){

        $language_id = $language['id'];
        $languageDir = $language['d_short'];

        $zoneName = $zone->getName();

        $parentPageId = \Ip\Module\Pages\Db::rootContentElement($zone->getId(), $language_id);


        if ($parentPageId === false) {
            trigger_error("Can't find root zone element.");

            return false;
        }

        $parentPage = $zone->getPage($parentPageId);





        $path = realpath(ipConfig()->fileDirFile('ImportExport'));

        $Directory = new \RecursiveDirectoryIterator($path);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $index = new \RegexIterator($Iterator, '/^.+\.json$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach($index as $name => $object){

            //TODO get page data from JSON
            $buttonTitle = basename($name, ".json");
            $url = $buttonTitle;

            print "Button title:".$buttonTitle;
            $pageId = \Ip\Module\Content\Service::addPage($zoneName, $parentPageId, $buttonTitle, $buttonTitle, $url);
            $revisionId = \Ip\Revision::createRevision($zoneName, $pageId, true);



            $string = file_get_contents( $name);

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
    }


}