<?php

namespace Plugin\ImportExport;


class ModelExport
{

    public static function getForm()
    {

        $form = new \Ip\Form();

        //$form->setAction(BASE_URL);

        $form->addClass('ipsExportForm');

        $field = new \Ip\Form\Field\Submit(
            array(
                'name' => 'submitExport', //html "name" attribute
                'value' => 'Export site to ZIP'
            ));

        $form->addField($field);

        $field = new \Ip\Form\Field\Hidden(
            array(
                'name' => 'action',
                'defaultValue' => 'export'
            )
        );

        $form->addField($field);

        $field = new \Ip\Form\Field\Hidden(
            array(
                'name' => 'aa',
                'value' => 'ImportExport.export'
            ));
        $form->addField($field);

        return $form;
    }


    public static function getLanguages()
    {
        $languages = ipContent()->getLanguages();
        return self::getExportLanguages($languages);
    }

    public static function getExportLanguages($languages)
    {
        global $site;

        $languageList = Array();

        foreach ($languages as $language) {

            $languageRecord['code'] = $language->getCode();
            $languageRecord['d_long'] = $language->getTitle();
            $languageRecord['d_short'] = $language->getAbbreviation();
            $languageRecord['urlPath'] = $language->getUrlPath();
//            $languageRecord['text_direction'] = $language->getTextDirection();
            $languageRecord['visible'] = $language->isVisible();
            $languageList[] = $languageRecord;
        }

        return $languageList;
    }


    public static function getExportMenus($menuLists)
    {

        $menuForExport = Array();

        foreach ($menuLists as $languageCode => $menuList) {
            foreach ($menuList as $menuItem) {
//                var_dump($menuItem);
                $item['name'] = $menuItem['alias'];
                $item['title'] = $menuItem['title'];
                $item['urlPath'] = $menuItem['urlPath'];
                $item['description'] = $menuItem['description'];
                $item['languageCode'] = $menuItem['languageCode'];
                $menuForExport[] = $item;
            }
        }

        return $menuForExport;
    }

    private static function getRevisionId($pageId)
    {

        $revisionTable = ipTable('revision');
        $sql = "
            SELECT * FROM $revisionTable
            WHERE
                `pageId` = ? AND
                `isPublished` = 1
            ORDER BY `createdAt` DESC, `revisionId` DESC
        ";
        $revision = ipDb()->fetchRow($sql, array($pageId));
        if ($revision) {
            return $revision['revisionId'];
        } else {
            return false;
        }


    }

    /**
     * Returns widget elements
     * @param $pageId
     */
    public function getElements($pageId)
    {

        /** @var \Ip\Internal\Revision $publishedRevision */
        $publishedRevisionId = self::getRevisionId($pageId);


        /** @var \Ip\Page $revisionId */
        $widgetRecords = ipDb()->selectAll(
            'widget', '*',
            array(
                'revisionId' => $publishedRevisionId,
                'isVisible' => 1,
                'isDeleted' => 0
            ),
            'ORDER BY position ASC'
        );

//
//        $widgetRecords = ipDb()->selectAll(
//            'widget', '*'
//                 );
//        $widgetRecords = \Modules\standard\content_management\Model::getBlockWidgetRecords('main', $revision['revisionId']); // TODO X blocks with different names

        $widgetData = array();

        if (!empty($widgetRecords)) {


            foreach ($widgetRecords as $widgetRecord) {

                try {

                    $widgetFiltered = self::getWidget($widgetRecord);

                    if ($widgetFiltered) {
                        $widgetData[] = $widgetFiltered;
                    }

                } catch (\Exception $e) {
                    Log::addRecord($e->getMessage());
                }
            }
        }

        return $widgetData;
    }

    public static function getWidget($widgetRecord)
    {

        if (isset($widgetRecord['name'])) {

            $widget['type'] = $widgetRecord['name'];

            if (isset($widgetRecord['skin'])) {
                $widget['layout'] = $widgetRecord['skin'];
            }

            if (isset($widgetRecord['blockName'])) {
                $widget['blockName'] = $widgetRecord['blockName'];
            }

            if (isset($widgetRecord['data'])) {
                $widget['data'] = json_decode($widgetRecord['data'], true);
                switch ($widget['type']) {
                    case 'Image':
                        self::copyWidgetFile($widget['data']['imageOriginal']);
                        break;
                    case 'Gallery':
                        self::copyWidgetGalleryFiles($widget['data']);
                        break;
                    case 'File':
                        self::copyFileWidgetFiles($widget['data']);
                        break;
                }
            } else {
                $widget = false;
            }

        } else {
            $widget = false;
        }

        return $widget;
    }


    public static function copyWidgetFile($widgetFile)
    {

        if (isset($widgetFile)) {

            $originalFileName = esc($widgetFile);
            $originalPathName = ipFile('file/repository/' . $originalFileName);
            if (file_exists($originalPathName)) {

                $archiveRepoPath = ManagerExport::getTempDir() . ManagerExport::ARCHIVE_DIR . '/file/';
                if (!is_dir($archiveRepoPath)) {
                    mkdir($archiveRepoPath, null, true);
                }

                $archiveRepoPathName = $archiveRepoPath . '/' . $originalFileName;
                copy($originalPathName, $archiveRepoPathName);

                return $originalFileName;
            } else {
                Log::addRecord('File ' . esc($widgetFile) . ' does not exist in repository', 'error');
            }

        } else {
            Log::addRecord('Widget image file is not set', 'error');
            return false;
        }

    }


    private static function copyFileWidgetFiles($widgetData)
    {
        if (!empty($widgetData['files'])) {
            foreach ($widgetData['files'] as $file) {
                self::copyWidgetFile($file['fileName']);
            }
        }
    }

    private static function copyWidgetGalleryFiles($widgetData)
    {

        if (!empty($widgetData['images'])) {
            foreach ($widgetData['images'] as $image) {
                self::copyWidgetFile($image['imageOriginal']);
            }

        }

    }


    public static function getPageSettings($pageId)
    {

        $retval = ipContent()->getPage($pageId); // TODO copy only some properties from a list below

        return $retval;
    }


    public static function getBlockNames()
    {

        $table = ipTable('widget');
        $sql = "SELECT DISTINCT `blockName` FROM $table WHERE `isVisible` = 1 and `isDeleted` = 0 ORDER BY `blockName` ASC";

        $results = ipDb()->fetchColumn($sql);

        return $results;

    }

} 