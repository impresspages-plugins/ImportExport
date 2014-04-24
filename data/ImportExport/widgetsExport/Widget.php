<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/23/14
 * Time: 12:26 PM
 */

namespace Modules\data\ImportExport\widgetsExport;

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

        return array($elements);
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

    protected static function copyImage($imageFileName)
    {
        $destination = \Modules\data\ImportExport\ManagerExport::getTempDir().
            \Modules\data\ImportExport\ManagerExport::ARCHIVE_DIR.'/'.$imageFileName;
        $dirName = dirname($destination);

        if (!is_dir($dirName)){
            mkdir($dirName, null,true);
        }

        if (copy(BASE_DIR.$imageFileName, $destination)){
            return true;
        }else{
            return false;
        }

    }

}

