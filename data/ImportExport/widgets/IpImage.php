<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/23/14
 * Time: 1:04 PM
 */
namespace Modules\data\ImportExport\widgets;

class IpImage extends Widget {

    public function getIp4Name() {
        return 'Image';
    }

    public function getData(){


        if (isset($this->data['imageOriginal'])){
            self::copyImage($this->data['imageOriginal']);
        }

        if (isset($this->data['text'])){
            $text = $this->data['text'];
        }else{
            $text = '';
        }

        return self::getSelectedWidgetParams($this->data, array('imageOriginal', 'cropX1', 'cropY1', 'cropX2', 'cropY2'));

    }

    private static function copyImage($imageFileName)
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
