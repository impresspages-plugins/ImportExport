<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 3/10/14
 * Time: 10:28 AM
 */

namespace Modules\data\ImportExport;


class ModelExport {

    public static function getFormExport()
    {

        $form = new  \Modules\developer\form\Form();

        //$form->setAction(BASE_URL);

        $form->addClass('ipsExportForm');

        $field = new \Modules\developer\form\Field\Submit(
            array(
                'name' => 'submitExport', //html "name" attribute
                'label' => 'submitExport', //field label that will be displayed next to input field
                'defaultValue' => 'Export site to ZIP'
            ));

        $form->addField($field);

        $field = new \Modules\developer\form\Field\Hidden(
            array(
                'name' => 'action',
                'defaultValue' => 'export'
            )
        );

        $form->addField($field);

        $field = new \Modules\developer\form\Field\Hidden(
            array(
                'name' => 'aa',
                'defaultValue' => 'export'
            ));
        $form->addField($field);

        $field = new \Modules\developer\form\Field\Hidden(
            array(
                'name' => 'g',
                'defaultValue' => 'data'
            ));
        $form->addField($field);

        $field = new \Modules\developer\form\Field\Hidden(
            array(
                'name' => 'm',
                'defaultValue' => 'ImportExport'
            ));
        $form->addField($field);

        return $form;
    }

    public static function getZones(){
        global $site;
        $zones = $site->getZones();
        return self::getExportZones($zones);

    }

    public static function getLanguages(){
        global $site;
        $languages = $site->getLanguages();
//        var_dump($languages);
        return self::getExportLanguages($languages);
    }

    public static function getExportLanguages($languages)
    {
        global $site;

        $languageList = Array();

        foreach ($languages as $language) {

            $languageRecord['code'] = $language->getCode();
            $languageRecord['d_short'] = $language->getShortDescription();
            $languageRecord['d_long'] = $language->getLongDescription();
            $languageRecord['url'] = $language->getUrl();
//            $languageRecord['text_direction'] = $language->getTextDirection();
            $languageRecord['visible'] = $language->getVisible();

            $languageList[] = $languageRecord;
        }

        return $languageList;
    }


    public static function getExportZones($zones)
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
    public function getElements($zoneName, $pageId){

        global $site;

        $revision = \Ip\Revision::getPublishedRevision($zoneName, $pageId);
        $widgetRecords = \Modules\standard\content_management\Model::getBlockWidgetRecords('main', $revision['revisionId']); // TODO X blocks with different names

        $widgetData = array();
        foreach ($widgetRecords as $widgetRecord) {
            try{

                $widgetData[] = self::getWidgetExportData($widgetRecord);
            }catch (\Exception $e){
                Log::addRecord($e);
            }
        }

        return $widgetData;
    }

    public static function getWidgetExportData($widgetRecord) {

        $widgetName = $widgetRecord['name'];

        $widgetClassName = "Modules\\data\\ImportExport\\widgets\\".$widgetName;

        if (!class_exists($widgetClassName, false /* do not attempt autoload */)) {
//            throw new \Exception("Unknown widget class ".$widgetClassName);
        }

        $widget = new $widgetClassName($widgetRecord);

        $elements  = $widget->getIp4Content();

        if (!$widget->isEnabled()){

            throw new \Exception('ERROR: Widget '. $widgetName.' not supported');
        }

        return $elements;
    }

    public static function getPageSettings($pageId) {
        global $site;


        $retval =  \Modules\standard\menu_management\Db::getPage($pageId); // TODO copy only some properties from a list below

        return $retval;
    }


} 