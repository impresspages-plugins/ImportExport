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

        $form->setAction(BASE_URL);

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
        //self::getInstances($pageId);

//        $page = $site->getCurrentElement();

        $revision = \Ip\Revision::getPublishedRevision($zoneName, $pageId);
//        var_dump($revision);
        $widgetRecords = \Modules\standard\content_management\Model::getBlockWidgetRecords('main', $revision['revisionId']); // TODO X blocks with different names
        //\Modules\standard\content_management\Model::getWidgetFullRecord($instanceId);

        $widgetData = array();

        foreach ($widgetRecords as $widgetRecord) {
            $widgetData[] = self::getWidgetExportData($widgetRecord);
        }

        return $widgetData;
    }

    public static function getWidgetExportData($widgetRecord) {
//        var_dump($widgetRecord);

        $widgetName = $widgetRecord['name'];
        $widgetData = $widgetRecord['data'];
        $processWidget = false;
        $content = array();

        switch ($widgetName) {
            case 'IpSeparator':
                $widgetName = 'Separator';
                $content = null;

                $elements = array ('type' => $widgetName,
                    'layout' => $widgetRecord['layout']);
                $processWidget = true;
                break;
            case 'IpTable':
                $widgetName = 'Text';

                if (isset($widgetData['text'])){
                    $content['text'] = $widgetData['text'];
                }else{
                    $content['text'] = '';
                }

                $elements = array ('type' => $widgetName,
                    'layout' => $widgetRecord['layout'],
                    'data' => array('text' => $content['text']));

                $processWidget = true;
                break;
            case 'IpText':
                $widgetName = 'Text';
                $content['text'] = $widgetData['text'];

                $elements = array ('type' => $widgetName,
                    'layout' => $widgetRecord['layout'],
                    'data' => array(
                        'text' => $widgetData['text']
                    ));

                $processWidget = true;
                break;
            case 'IpTextImage': //  IpTextImage as IpText
                $widgetName = 'TextImage';
                $content['text'] = $widgetData['text'];

                $elements = array ('type' => $widgetName,
                    'layout' => $widgetRecord['layout'],
                    'data' => array(
                        'imageSmall' => $widgetData['text'],
                        'title' => $widgetData['title'],
                        'text' => $content['text']
                    ));

                $processWidget = true;
                break;

            case 'IpTitle':
                $widgetName = 'Title';
                if (isset($widgetData['title'])) {
                    $content['title'] = $widgetData['title'];
                }else{
                    $content['title'] = '';
                }

                $elements = array ('type' => $widgetName,
                    'layout' => $widgetRecord['layout'],
                    'data' => array(
                        'title' => $content['title']
                    ));

                $processWidget = true;
                break;

            case 'IpHtml':
                $widgetName = 'Html';
                $content['html'] = $widgetData['html'];
                if (!isset($widgetValue['layout'])) {
                    $layout = 'escape'; // default layout for code examples
                }

                $elements = array ('type' => $widgetName,
                    'layout' => $widgetRecord['layout'],
                    'data' => array(
                        'html' => $content['html'],
                    ));

                $processWidget = true;
                break;

            case 'ipFiledoc':
                $widgetName = 'FileDoc';

                $elements = array ('type' => $widgetName,
                    'layout' => $widgetRecord['layout'],
                    'data' => array(
                        'pageFile' => $widgetData['pageFile'],
                    ));

                $processWidget = true;

                break;

            case 'ipCodeHighlight':
                $widgetName = 'CodeHighlight';
                $elements = array ('type' => $widgetName,
                    'layout' => $widgetRecord['layout'],
                    'data' => array(
                        'mode' => $widgetData['mode'],
                        'hlLine' => $widgetData['hlLine'],
                        'showLines' => $widgetData['showLines'],
                        'code' => $widgetData['code'],
                    ));
                $processWidget = true;
                break;

            case 'ipCustomMenu':
                $widgetName = 'CustomMenu';
                $elements = array ('name' => $widgetName,
                    'layout' => $widgetRecord['layout'],
                    'data' => array(
                        'menuMode' => $widgetData['menuMode'],
                        'menuDepth' => $widgetData['menuDepth'],
                        'pages' => $widgetData['pages'],
                    ));

                break;

            default:
                $content = null;
                $elements = null;
                break;
        }

        if (!$processWidget){
            var_dump('ERROR: Widget '. $widgetName.' not supported');
        }


        return $elements;
    }

    public static function getPageSettings($pageId) {
        global $site;


        $retval =  \Modules\standard\menu_management\Db::getPage($pageId); // TODO copy only some properties from a list below
//        $retval = array(
//            'id' => $pageId,
//            'row_number' => $page->,
//            parent,
//            button_title,
//            visible,
//            page_title,
//            keywords,
//            description,
//            url,
//            last_modified,
//            created_on,
//            type,
//            redirect_url
//        );

        return $retval;
    }



} 