<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/23/14
 * Time: 1:04 PM
 */
namespace Modules\data\ImportExport\widgets;

class IpText extends Widget {

    public function getIp4Name() {
        return 'Text';
    }

    public function getData(){

        if (isset($this->data['text'])){
            $text = $this->data['text'];
        }else{
            $text = '';
        }

        return array('text' => $text);

    }

}
