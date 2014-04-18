<?php
/**
 * Created by PhpStorm.
 * User: Marijus
 * Date: 4/18/14
 * Time: 1:07 PM
 */

namespace Modules\data\ImportExport;


class Log {

    protected static $log = array();

    public static  function addRecord($msg, $status = 'warning')
    {
        self::$log[] = Array('message' => $msg, 'status' => $status);

    }

    public static function getLog(){

        $allLogRecords = self::$log;

        return self::$log;
    }

} 