<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/23/14
 * Time: 1:04 PM
 */
namespace Modules\data\ImportExport\widgetsExport;

class IpImageGallery extends Widget
{

    public function getIp4Name()
    {
        return 'Gallery';
    }

    public function getData()
    {


        $images = array();

        if (isset($this->data['images'])) {
            foreach ($this->data['images'] as $image) {

                if (isset($image['imageOriginal'])) {
                    self::copyImage($image['imageOriginal']);
                }

                $images[] = self::getSelectedWidgetParams($image, array('imageOriginal', 'cropX1', 'cropY1', 'cropX2', 'cropY2', 'title'));

            }
        }

        return array('images' => $images);

    }


}
