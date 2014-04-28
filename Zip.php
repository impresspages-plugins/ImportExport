<?php

namespace Plugin\ImportExport;

class Zip
{

    public static function zip($path, $archiveDir, $archiveFileName)
    {

        require_once(__DIR__ . '/lib/pclzip.lib.php');

        $cnt = '';

        while (file_exists($path . '/' . $archiveFileName . $cnt . '.zip')) {
            $cnt++;
        }


        if ($cnt) {
            $archiveFileName .= $cnt;
        }

        $archiveFileName .= '.zip';

        $archive = new \PclZip($path . '/' . $archiveFileName);

        Log::addRecord('Copying to archive');
        $v_dir = $path . $archiveDir; // or dirname(__FILE__);
        $archive->add($v_dir, PCLZIP_OPT_REMOVE_PATH, $path);
        return $archiveFileName;
    }

    public static function extractZip($file)
    {
        $extractSubDir = false;

        $fileName = basename($file);

        try {
            $zipLib = ipFile('Plugin/ImportExport/lib/pclzip.lib.php');
            require_once($zipLib);

            $archive = new \PclZip(ipFile('file/secure/tmp/' . $fileName));

            $zipNameNoExt = basename($fileName, '.zip');
            $extractSubDir = $zipNameNoExt;
            $count = 0;
            while (is_file(ipFile('file/secure/tmp/' . $extractSubDir)) || is_dir(
                    ipFile('file/secure/tmp/' . $extractSubDir)
                )) {
                $count++;
                $extractSubDir = $zipNameNoExt . '_' . $count;
            }

            if ($archive->extract(
                    PCLZIP_OPT_PATH,
                    ipFile('file/secure/tmp'),
                    PCLZIP_OPT_ADD_PATH,
                    $extractSubDir
                ) == 0
            ) {
                die("Error : " . $archive->errorInfo(true));
            }
        } catch (\Exception $e) {
            Log::addRecord($e);
        }
        return $extractSubDir;
    }
}