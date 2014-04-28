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
            $languageRecord['url'] = $language->getUrlPath();
//            $languageRecord['text_direction'] = $language->getTextDirection();
            $languageRecord['visible'] = $language->isVisible();
            $languageList[] = $languageRecord;
        }

        return $languageList;
    }


    public static function getExportMenus($zones)
    {

        $zoneList = Array();

        foreach ($zones as $zone) {
            $item['name'] = $zone->getName();
            $item['title'] = $zone->getTitle();
            $item['url'] = $zone->getUrl();
            $item['description'] = $zone->getDescription();
            $zoneList[] = $item;
        }

        return $zoneList;
    }

    /**
     * Returns widget elements
     * @param $pageId
     */
    public function getElements($zoneName, $pageId)
    {

        global $site;

        $revision = \Ip\Revision::getPublishedRevision($zoneName, $pageId);
        $widgetRecords = \Modules\standard\content_management\Model::getBlockWidgetRecords('main', $revision['revisionId']); // TODO X blocks with different names

        $widgetData = array();
        foreach ($widgetRecords as $widgetRecord) {

            try {

                $widget = self::getWidget($widgetRecord);
                if (!$widget->isEnabled()) {
                    throw new \Exception('ERROR: Widget ' . $widgetRecord['name'] . ' not supported');
                }

                $widgetContent = $widget->getIp4Content();

                foreach ($widgetContent as $widgetContentItem) {
                    $widgetData[] = $widgetContentItem;
                }

            } catch (\Exception $e) {
                Log::addRecord($e->getMessage());
            }
        }

        return $widgetData;
    }

    public static function getWidget($widgetRecord)
    {


        $widgetName = $widgetRecord['name'];

        $widgetClassName = "\\Modules\\data\\ImportExport\\widgetsExport\\" . $widgetName;

        if (!class_exists($widgetClassName)) {
            throw new \Exception("Warning: skipping non-exportable widget " . $widgetClassName);
        }

        try {
            $widget = new $widgetClassName($widgetRecord);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        return $widget;
    }

    public static function getPageSettings($pageId)
    {
        global $site;


        $retval = \Modules\standard\menu_management\Db::getPage($pageId); // TODO copy only some properties from a list below

        return $retval;
    }


} 