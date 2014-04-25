<?php

namespace Modules\data\ImportExport\widgetsExport;


interface iWidget
{
    public function getIp4Name();

    public function isEnabled();

    public function getIp4Content();
}