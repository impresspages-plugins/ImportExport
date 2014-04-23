<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/23/14
 * Time: 1:04 PM
 */
namespace Modules\data\ImportExport\widgets;

class IpHtml extends Widget {

    public function getIp4Name() {
        return 'Html';
    }

    public function getData(){

        if (isset($this->data['html'])){
            $text = $this->data['html'];
        }else{
            $text = '';
        }

        return array('html' => $text);

    }

}
