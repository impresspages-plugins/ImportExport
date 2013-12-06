<?php
/**
 *
 * Simple Form post plugin example
 *
 * @package   ImpressPages
 * @copyright Copyright (C) 2009 JSC Apro media.
 * @license   GNU/GPL, see license.html
 */
namespace Modules\data\export;

//use Modules\standard\menu_management\Model;

use Modules\data\export\Template;

if (!defined('BACKEND')) {
    exit;
} //this file can be acessed only in backend

require_once(__DIR__ . '/model.php'); //require class to interact with database

class Manager
{

    const PLUGIN_TEMP_DIR = 'tmp/data/export/',
        ARCHIVE_DIR = 'archive/',
        ZONE_FILE = 'info',
        VERSION = '4';

    /**
     *
     * method that is executed when displaying plugin tab
     */
    var $errors = null,
        $downloadLink = '';

    private static $instance = null;

    function manage()
    {
        global $cms,
               $site;


        $site->requireTemplate('data/export/template.php');
        $actionUrl = $cms->generateUrl();


        $answer = Template::addHtmlHead();

        if (isset($_POST['impexp_action'])) {
            switch ($_POST['impexp_action']) {
                case 'export':
                    print "Exporting";
                    $answer .= $this->exportSiteTree();
                    print "EOF TEST";
                    if (sizeof($this->errors) > 0) {
                        $answer .= 'Errors:';
                        $answer .= implode('<br/>ERROR:', $this->errors);
                    }

                    $answer .= Template::generateExportComplete($actionUrl, $this->downloadLink);
                    break;
                case 'import':
                    $answer .= "Importing";
                    //TODO
                    break;
                //TODO other actions
                default:
                    $answer .= Template::generateForm($actionUrl);
                    break;
            }

        } else {
            $answer .= Template::generateForm($actionUrl);
        }

        /**
         * Returned value will be displayed
         */
        return $answer;
    }


    private function getPages($zone, $languageId, $maxDepth = 1000, $parentId = null, $curDepth = 1, $path)
    {
        global $site;
        $pages = array();
        if ($curDepth <= $maxDepth) {

            $tmpElements = $zone->getElements($languageId, $parentId, 0, null, true);

            foreach ($tmpElements as $key => $element) {

                if ($element->getType() == 'default') {
                    $pages[] = $element;
                }

                $dirName = $element->getUrl();

                $pages = array_merge(
                    $pages,
                    $this->getPages(
                        $zone,
                        $languageId,
                        $maxDepth,
                        $element->getId(),
                        $curDepth + 1,
                        $path . "/" . $dirName
                    )
                );

                $model = new Model;
                try {

                    // Add page button, title, visibility, meta title, meta keywords, meta description
                    // URL, redirect type, redirect to external page URL and RSS settings.
                    $content = Array();
                    $content['settings'] = $this->getPageSettings($element->getId());

                    $widgetData  = $model->getElements($element->getId());

                    if (!is_null($widgetData)){
                        $content['widgets'] = $widgetData;
                    }

                } catch (\Exception $e) {
                    print "Export error in " . $path . "/" . $dirName . " - " . $e->getMessage();
                    print '<br>';
                }

                if (isset($content)) {
                    $this->savePages($content, $path, $dirName);
                }
            }
        }
        return $pages;
    }

    private function savePages($content, $path, $saveFileName)
    {
        $path = preg_replace('/[^\/a-zA-Z0-9_-]$/s', '', $path);
        $saveFileName = preg_replace('/[^a-zA-Z0-9_-]$/s', '', $saveFileName);
        print 'doing';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $fh = fopen($path . '/' . $saveFileName . '.json', 'w');

        fwrite($fh, json_encode($content));

        fclose($fh);

    }

    private function exportSiteTree()
    {

        global $site;

        require_once(__DIR__ . '/Zip.php');

        $languages = $site->getLanguages();
        /** @var \Frontend\Zone[] $zones */
        /** @var \Frontend\Language[] $language */
        $zones = $site->getZones();
        $answer = '';

        $this->saveSiteSettings($zones, $languages);

        foreach ($languages as $language) {


            $language_id = $language->getId();
            $language_url = $language->getUrl();

            foreach ($zones as $zone) {

                print "<br/>PROCESSING ZONE" . $zone->getName() . ' for LANGUAGE url:' . $language_url . '<br>';

                if ($zone->getAssociatedModule() == 'content_management') {

                    $zoneName = $zone->getName();


                    $this->getPages(
                        $zone,
                        $language_id,
                        1000,
                        null,
                        1,
                        self::getTempDir() . self::ARCHIVE_DIR . $language_url . "_" . $zoneName
                    );
                }
            }

        }


        $zipFileName = $this->setZipFileName();

        Zip::zip(self::getTempDir(), self::ARCHIVE_DIR, $zipFileName);

        $this->delTree(self::getTempDir() . self::ARCHIVE_DIR);

        $this->downloadLink = BASE_URL . FILE_DIR . self::PLUGIN_TEMP_DIR . $zipFileName;

        return $answer;

    }

    public function addError($err)
    {
        $this->errors[] = $err;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Manager;
        }
        return self::$instance;
    }


    private function saveSiteSettings($zones, $languages)
    {

        global $site;

        $path = $this->getTempDir() . self::ARCHIVE_DIR;

        $saveFileName = self::ZONE_FILE;

        print 'saving zone';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }


        $content = Array();


        $content['version'] = self::VERSION;
        $content['zones'] = Model::getExportZones($zones);
        $content['languages'] = Model::getExportLanguages($languages);

        $fh = fopen($path . '/' . $saveFileName . '.json', 'w');

        fwrite($fh, json_encode($content));

        fclose($fh);
    }

    private function saveZone($zoneName, $content)
    {
//        print "<h1>Save zone:".$zoneName."</h1>";

    }

    private function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function getTempDir()
    {
        return BASE_DIR . FILE_DIR . self::PLUGIN_TEMP_DIR;
    }

    public function setZipFileName()
    {

        return "archive_" . time() . ".zip";
    }


    private function getPageSettings($pageId)
    {
        $settings = Model::getPageSettings($pageId);
        return $settings;
    }


}

