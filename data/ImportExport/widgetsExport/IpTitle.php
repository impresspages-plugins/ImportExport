<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/23/14
 * Time: 1:04 PM
 */
namespace Modules\data\ImportExport\widgetsExport;

class IpTitle extends Widget {

    public function getIp4Name() {
        return 'Heading';
    }

    public function getData(){

        if (isset($this->data['title'])){
            $text = $this->data['title'];
        }else{
            $text = '';
        }

        return array('title' => $text);

    }

}
