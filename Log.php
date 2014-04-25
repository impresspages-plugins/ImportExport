<?php

namespace Plugin\ImportExport;


class Log
{

    protected static $log = array();

    public static function addRecord($msg, $status = 'warning')
    {
        self::$log[] = Array('message' => $msg, 'status' => $status);

    }

    public static function getLog()
    {

        $allLogRecords = self::$log;

        return self::$log;
    }

}