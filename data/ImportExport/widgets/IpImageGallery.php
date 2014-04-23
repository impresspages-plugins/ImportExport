<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/23/14
 * Time: 1:04 PM
 */
namespace Modules\data\ImportExport\widgets;

class IpImageGallery extends Widget {

    public function getIp4Name() {
        return 'Gallery';
    }

    public function getData(){


        $images = array();

        foreach ($this->data['images'] as $image){

            if (isset($image['imageOriginal'])){
                self::copyImage($image['imageOriginal']);
            }

            $images[] = self::getSelectedWidgetParams($image, array('imageOriginal', 'cropX1', 'cropY1', 'cropX2', 'cropY2', 'title'));

        }

        return array('images' => $images);

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
