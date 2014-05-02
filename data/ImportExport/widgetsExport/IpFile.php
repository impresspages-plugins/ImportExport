<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/23/14
 * Time: 1:04 PM
 */
namespace Modules\data\ImportExport\widgetsExport;

class IpFile extends Widget
{

    public function getIp4Name()
    {
        return 'File';
    }

    public function getData()
    {

        $files = array();

        if (isset($this->data['files'])) {
            foreach ($this->data['files'] as $file) {

                if (isset($file['fileName'])) {
                    self::copyImage($file['fileName']);
                    $file['fileName'] = basename($file['fileName']);
                }

                $files[] = self::getSelectedWidgetParams($file, array('fileName', 'title'));

            }
        }

        return array('files' => $files);
    }


}
