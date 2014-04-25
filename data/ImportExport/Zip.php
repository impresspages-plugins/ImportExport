<?php

namespace Modules\data\ImportExport;

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
        $v_list = $archive->add($v_dir, PCLZIP_OPT_REMOVE_PATH, $path);
        return $archiveFileName;
    }

    public static function extractZip($file)
    {
        $extractSubDir = false;

        $fileName = $file->getOriginalFileName();

        try {
            require_once(__DIR__ . '/lib/pclzip.lib.php');

            $archive = new \PclZip(BASE_DIR . 'file/secure/tmp/' . $fileName);

            $zipNameNoExt = basename($fileName, '.zip');
            $extractSubDir = $zipNameNoExt;
            $count = 0;
            while (is_file(BASE_DIR . 'file/secure/tmp/' . $extractSubDir) || is_dir(
                    BASE_DIR . 'file/secure/tmp/' . $extractSubDir
                )) {
                $count++;
                $extractSubDir = $zipNameNoExt . '_' . $count;
            }

            if ($archive->extract(
                    PCLZIP_OPT_PATH,
                    BASE_DIR . 'file/secure/tmp',
                    PCLZIP_OPT_ADD_PATH,
                    $extractSubDir
                ) == 0
            ) {
                die("Error : " . $archive->errorInfo(true));
            }
        } catch (\Exception $e) {
            Log::addRecord($e);
            throw new \Exception($e); //            $this->addLogRecord($e);
        }
        return $extractSubDir;
    }
}