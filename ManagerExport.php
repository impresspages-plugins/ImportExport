<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 */

namespace Plugin\ImportExport;


class ManagerExport
{

    const PLUGIN_TEMP_DIR = 'tmp/data/export/',
        ARCHIVE_DIR = 'archive',
        ZONE_FILE = 'info',
        VERSION = '4';

    public static function exportSiteTree()
    {

        global $site;

        $languages = self::getLanguages();
        $menuLists = self::getTopLevelMenus($languages);

        self::saveSiteSettings($menuLists, $languages);

        foreach ($languages as $language) {


            $language_id = $language->getId();
            $language_url = $language->getUrlPath();

            foreach ($menuLists as $menuList) {

                Log::addRecord("Processing menu " . $menuList->getName() . ' for LANGUAGE url:' . $language_url);

                if ($menuList->getAssociatedModule() == 'content_management') {

                    $zoneName = $menuList->getName();

                    try {
                        self::getPages(
                            $menuList,
                            $language_id,
                            1000,
                            null,
                            1,
                            self::getTempDir() . self::ARCHIVE_DIR . '/' . $language_url . "_" . $zoneName
                        );
                    } catch (Exception $e) {
                        throw \Exception("ERROR. Error while exporting site tree " . $e);
                    }

                }
            }

        }

        $zipFileName = self::setZipFileName();

        try {
            $archiveFileName = Zip::zip(self::getTempDir(), self::ARCHIVE_DIR, $zipFileName);

            $archiveFullPath = self::getTempDir() . self::ARCHIVE_DIR;

            if (is_dir($archiveFullPath)) {
                self::delTree($archiveFullPath);
            }

        } catch (\Exception $e) {
            throw ($e);
        }

        return ipFile('file/tmp/data/export/' . $archiveFileName);

    }

    private static function getTopLevelMenus($languageCode)
    {
        $menus = $menu = \Ip\Internal\Pages\Service::getMenus($languageCode);
        return $menus;
    }

    private static function getLanguages()
    {
        $languages = ipContent()->getLanguages();
        return $languages;
    }
    // By nbari at dalmp dot com. http://www.php.net/manual/en/function.rmdir.php
    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private static function setZipFileName()
    {
        return "archive_" . date('Y-m-d_Hi');
    }

    private static function saveSiteSettings($zones, $languages)
    {

        global $site;

        $path = self::getTempDir() . self::ARCHIVE_DIR;

        $saveFileName = self::ZONE_FILE;

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $content = Array();

        $content['version'] = self::VERSION;
        $content['menuLists'] = ModelExport::getExportMenus($zones);
        $content['languages'] = ModelExport::getLanguages($languages);

        $fh = fopen($path . '/' . $saveFileName . '.json', 'w');

        fwrite($fh, json_encode($content));

        fclose($fh);
    }


    public static function getTempDir()
    {
        return ipFile(self::PLUGIN_TEMP_DIR);
    }


    private static function getPages($zone, $languageId, $maxDepth = 1000, $parentId = null, $curDepth = 1, $path)
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
                    self::getPages(
                        $zone,
                        $languageId,
                        $maxDepth,
                        $element->getId(),
                        $curDepth + 1,
                        $path . "/" . str_pad($key, 4, '0', STR_PAD_LEFT) . '_' . $dirName
                    )
                );

                $model = new ModelExport();
                try {

                    // Add page button, title, visibility, meta title, meta keywords, meta description
                    // URL, redirect type, redirect to external page URL and RSS settings.
                    $content = Array();
                    $content['settings'] = self::getPageSettings($element->getId());
                    $content['settings']['position'] = $key;
                    $widgetData = $model->getElements($zone->getName(), $element->getId());

                    if (!is_null($widgetData)) {
                        $content['widgets'] = $widgetData;
                    }

                } catch (\Exception $e) {
                    Log::addRecord("Export error when exporting to " . $path . " Directory: " . $dirName . " - " . $e->getMessage());
                }


                if (isset($content)) {
                    try {
                        self::savePages($content, $path, str_pad($key, 4, '0', STR_PAD_LEFT) . '_' . $dirName);
                    } catch (\Exception $e) {
                        Log::addRecord("Export error when saving to " . $path . " Directory: " . $dirName . " - " . $e->getMessage());
                    }
                }
            }
        }
        return $pages;
    }

    private static function getPageSettings($pageId)
    {
        $allSettings = ModelExport::getPageSettings($pageId);

        $settings = array(
            'id' => $allSettings['id'],
            'parent' => $allSettings['parent'],
            'button_title' => $allSettings['button_title'],
            'visible' => $allSettings['visible'],
            'page_title' => $allSettings['page_title'],
            'keywords' => $allSettings['keywords'],
            'description' => $allSettings['description'],
            'url' => $allSettings['url'],
            'last_modified' => $allSettings['last_modified'],
            'created_on' => $allSettings['created_on'],
            'type' => $allSettings['type'],
            'redirect_url' => $allSettings['redirect_url']
        );


        return $settings;
    }

    private static function savePages($content, $path, $saveFileName)
    {

        $path = preg_replace('/[^\/a-zA-Z0-9_-]$/s', '', $path);
        $saveFileName = preg_replace('/[^a-zA-Z0-9_-]$/s', '', $saveFileName);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $fh = fopen($path . '/' . $saveFileName . '.json', 'w');

        fwrite($fh, json_encode($content));

        fclose($fh);

        return true;

    }


}