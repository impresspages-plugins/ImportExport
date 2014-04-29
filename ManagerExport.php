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
        foreach ($languages as $language){
            $menuLists[$language->getCode()] = self::getTopLevelMenus($language->getCode());
        }

        self::saveSiteSettings($menuLists, $languages);

        foreach ($menuLists as $languageCode=>$menuList) {

//            var_dump($menuList);

            foreach ($menuList as $menuItem){

                $menuAlias = $menuItem['alias'];

                Log::addRecord("Processing menu " . $menuItem['alias'] . ' for language code:' . $languageCode);

                try {
                    self::getPages(
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

        return ipFile('file/tmp/data/export/' . $archiveFileName);

    }

    private static function getTopLevelMenus($languageCode)
    {
        $menus = \Ip\Internal\Pages\Service::getMenus($languageCode);
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



    private static function getPages($menu, $path)
    {
        $tmpElements = \Ip\Menu\Helper::getMenuItems($menu['alias'], 1, 1000);

        self::exportPages($tmpElements, $menu['alias'], 1);
    }

    private static function exportPages($pages, $path, $exportedPageId){
        foreach ($pages as $key=>$page){
            /** @var \Ip\Menu\Item $page */
            $exportedPageId++;
            $children = $page->getChildren();
            if (!is_null($children)){
                self::exportPages($children, $path.'/'.str_pad($exportedPageId, 4, '0', STR_PAD_LEFT), $exportedPageId);
            }

//TODO           $path . "/" . str_pad($key, 4, '0', STR_PAD_LEFT) . '_' . $dirName;

            self::saveFile($page, $path.'/'.str_pad($exportedPageId, 4, '0', STR_PAD_LEFT), $exportedPageId, $exportedPageId);
        }

    }
//            $tmpElements = $menu->getElements($languageId, $parentId, 0, null, true);

    private static function saveFile($menuItem, $path, $position)
    {

        /** @var \Ip\Menu\Item $page */
        $page = $menuItem->getTarget();

        // where alias = 'name', langugage, isDeleted

        $list = ipDb()->selectAll('page', '*', array('alias'=>$page['alias'], 'isDeleted'=>0));

        $pages = \Ip\Page::createList($list);

        foreach ($pages as $page){
    //        var_dump($page);
            /** @var $page \Ip\Page */
            $pageId = $page->getId();

            $model = new ModelExport();
            try {

                // Add page button, title, visibility, meta title, meta keywords, meta description
                // URL, redirect type, redirect to external page URL and RSS settings.
                $content = Array();
                $settings = self::getPageSettings($pageId);
//            var_dump($settings);
                $content['settings'] = $settings;
                $content['settings']['position'] = $position;
                $widgetData = $model->getElements($pageId);

                if (!empty($widgetData)) {
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
    }

    private static function getPageSettings($pageId)
    {
        $allSettings = ModelExport::getPageSettings($pageId);

        /** @var $allSettings \Ip\Page */

        //$allSettings->buttontitle?


        $settings = array(
            'id' => $allSettings->getId(),
            'parent' => $allSettings->getParentId(),
            'button_title' => $allSettings->getAlias(),
            'visible' => $allSettings->isVisible(),
            'page_title' => $allSettings->getTitle(),
            'keywords' => $allSettings->getKeywords(),
            'description' => $allSettings->getDescription(),
            'url' => $allSettings->getUrlPath(),
            'last_modified' => $allSettings->getUpdatedAt(),
            'created_on' => $allSettings->getCreatedAt(),
            'type' => $allSettings->getType(),
            'redirect_url' => $allSettings->getRedirectUrl()
        );


        return $settings;
    }

    private static function savePages($content, $path, $saveFileName)
    {

        $path = preg_replace('/[^\/a-zA-Z0-9_-]$/s', '', $path);
        $saveFileName = preg_replace('/[^a-zA-Z0-9_-]$/s', '', $saveFileName);
        $saveFullPath = ipFile(self::PLUGIN_TEMP_DIR.self::ARCHIVE_DIR.'/'.$path) ;

        if (!file_exists($saveFullPath)) {
            mkdir($saveFullPath, 0777, true);
        }



        $fh = fopen($saveFullPath. '/' . $saveFileName . '.json', 'w');

        fwrite($fh, json_encode($content));

        fclose($fh);

        return true;

    }


}