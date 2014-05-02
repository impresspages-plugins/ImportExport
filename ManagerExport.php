<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 */

namespace Plugin\ImportExport;


class ManagerExport
{

    const PLUGIN_TEMP_DIR = 'file/tmp/Export/',
        ARCHIVE_DIR = 'archive',
        ZONE_FILE = 'info',
        VERSION = '4';

    public static function exportSiteTree()
    {

        global $site;

        $languages = self::getLanguages();

        $menuLists = null;
        foreach ($languages as $language) {
            $menuLists[$language->getCode()] = self::getTopLevelMenus($language->getCode());
        }

        self::saveSiteSettings($menuLists, $languages);

        foreach ($menuLists as $languageCode => $menuList) {

//            var_dump($menuList);

            foreach ($menuList as $menuItem) {

                $menuAlias = $menuItem['alias'];

                Log::addRecord("Processing menu " . $menuItem['alias'] . ' for language code:' . $languageCode);

                try {
                    self::exportMenuPages(
                        $menuItem,
                        self::getTempDir() . self::ARCHIVE_DIR . '/' . $languageCode . "_" . $menuAlias
                    );
                } catch (Exception $e) {
                    throw \Exception("ERROR. Error while exporting site tree " . $e);
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

        return ipFileUrl(self::PLUGIN_TEMP_DIR . $archiveFileName);

    }

    private static function getLanguages()
    {
        $languages = ipContent()->getLanguages();
        return $languages;
    }

    private static function getTopLevelMenus($languageCode)
    {
        $menus = \Ip\Internal\Pages\Service::getMenus($languageCode);
        return $menus;
    }

    // By nbari at dalmp dot com. http://www.php.net/manual/en/function.rmdir.php

    private static function saveSiteSettings($menuLists, $languages)
    {

        $path = self::getTempDir() . self::ARCHIVE_DIR;

        $saveFileName = self::ZONE_FILE;

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $content = Array();

        $content['version'] = self::VERSION;
        $content['menuLists'] = ModelExport::getExportMenus($menuLists);
        $content['languages'] = ModelExport::getLanguages($languages);

        $fh = fopen($path . '/' . $saveFileName . '.json', 'w');

        fwrite($fh, json_encode($content));

        fclose($fh);
    }

    public static function getTempDir()
    {
        return ipFile(self::PLUGIN_TEMP_DIR);
    }

    private static function exportMenuPages($menu, $path)
    {
        $pages = ipContent()->getChildren($menu['id']);
        self::exportPages($pages, $path, 1);

    }

    private static function exportPages($pages, $path, $exportedPageId)
    {
        foreach ($pages as $key => $page) {
            /** @var \Ip\Menu\Item $page */
            $exportedPageId++;
            $children = $page->getChildren();
            if (!empty($children)) {
                self::exportPages($children, $path . '/' . str_pad($exportedPageId, 4, '0', STR_PAD_LEFT), $exportedPageId);
            }

//TODO           $path . "/" . str_pad($key, 4, '0', STR_PAD_LEFT) . '_' . $dirName;

            self::saveFile($page, $path, $exportedPageId);
        }

    }

    private static function saveFile($page, $path, $position)
    {

        // where alias = 'name', langugage, isDeleted


        $model = new ModelExport();
        try {
            $pageId = $page->getId();
            // Add page button, title, visibility, meta title, meta keywords, meta description
            // URL, redirect type, redirect to external page URL and RSS settings.
            $content = Array();
            $settings = self::getPageSettings($pageId);
//            var_dump($settings);
            $content['settings'] = $settings;
            $content['settings']['position'] = $position;
            $widgetData = $model->getElements($pageId);

            if ($widgetData) {
                $content['widgets'] = $widgetData;
            }

        } catch (\Exception $e) {
            Log::addRecord("Export error when exporting to " . $path . " Directory: " . $position . " - " . $e->getMessage());
        }

//            var_dump($content);

        if (!empty($content)) {
            try {
                self::savePages($content, $path, str_pad($position, 4, '0', STR_PAD_LEFT));
            } catch (\Exception $e) {
                Log::addRecord("Export error when saving to " . $path . " File: " . $position . " - " . $e->getMessage());
            }
        }

    }

    private static function getPageSettings($pageId)
    {
        $allSettings = ModelExport::getPageSettings($pageId);

        /** @var $allSettings \Ip\Page */

        //$allSettings->buttontitle?


        $settings = array(
            'alias' => $allSettings->getAlias(),
            'createdAt' => $allSettings->getCreatedAt(),
            'description' => $allSettings->getDescription(),
            'id' => $allSettings->getId(),
            'isBlank' => $allSettings->isBlank(),
            'isDisabled' => $allSettings->isDisabled(),
            'isSecured' => $allSettings->isSecured(),
            'isVisible' => $allSettings->isVisible(),
            'keywords' => $allSettings->getKeywords(),
            'languageCode' => $allSettings->getLanguageCode(),
            'layout' => ipPageStorage($pageId)->get('layout', 'main.php'),
            'metaTitle' => $allSettings->getMetaTitle(),
            'parentId' => $allSettings->getParentId(),
            'redirectUrl' => $allSettings->getRedirectUrl(),
            'title' => $allSettings->getTitle(),
            'type' => $allSettings->getType(),
            'updatedAt' => $allSettings->getUpdatedAt(),
            'urlPath' => $allSettings->getUrlPath(),
        );


        return $settings;
    }

//            $tmpElements = $menu->getElements($languageId, $parentId, 0, null, true);

    private static function savePages($content, $path, $saveFileName)
    {

        $path = preg_replace('/[^\/a-zA-Z0-9_-]$/s', '', $path);
        $saveFileName = preg_replace('/[^a-zA-Z0-9_-]$/s', '', $saveFileName);
//        $saveFullPath = ipFile(self::PLUGIN_TEMP_DIR.self::ARCHIVE_DIR.'/'.$path) ;

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }


        $fh = fopen($path . '/' . $saveFileName . '.json', 'w');

        fwrite($fh, json_encode($content));

        fclose($fh);

        return true;

    }

    private static function setZipFileName()
    {
        return "archive_" . date('Y-m-d_Hi');
    }

    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }


}