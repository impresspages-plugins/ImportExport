<?php
/**
 * Created by JetBrains PhpStorm.
 * User: marijus
 * Date: 13.11.26
 * Time: 14.01
 * To change this template use File | Settings | File Templates.
 */

namespace Modules\data\export;


class Zip
{

    public static function zip($path, $archiveDir, $archiveFileName)
    {

        require_once(__DIR__ . '/lib/pclzip.lib.php');
        $archive = new \PclZip($path . '/' . $archiveFileName);

        print "ARCHIVING TO:".$path . $archiveFileName;
        $v_dir = $path . $archiveDir; // or dirname(__FILE__);
        $v_list = $archive->add($v_dir, PCLZIP_OPT_REMOVE_PATH, $path);

    }

}