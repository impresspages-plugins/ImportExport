<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/23/14
 * Time: 12:26 PM
 */

namespace Modules\data\ImportExport\widgets;

abstract class Widget implements iWidget {

    public $layout = '',
           $name = '',
           $data = array(),
           $record = array();

    public function __construct($widgetRecord){

        $this->record = $widgetRecord;
        $this->data= $widgetRecord['data'];
        $this->layout = $widgetRecord['layout'];

    }

    public function isEnabled() {
        return true;
    }

    public function getLayout(){
        return $this->layout;
    }

    public function getData(){
        return $this->data;
    }


    public function getIp4Content() {

        $widgetName = $this->getIp4Name();

        $elements = array (
            'type' => $widgetName,
            'layout' => $this->getLayout()
        );


        $data = $this->getData();

        if (!empty($data)){
            $elements['data'] = $data;
        }
        return $elements;
    }


    protected  static function getSelectedWidgetParams($widgetData, $paramsList){

        $params = array();

        foreach ($paramsList as $paramKey){
            if (isset($widgetData[$paramKey])){
                $params[$paramKey] = $widgetData[$paramKey];
            }

        }
        return $params;
    }


}

