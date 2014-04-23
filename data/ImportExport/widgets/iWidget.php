<?php

namespace Modules\data\ImportExport\widgets;


interface iWidget {
    public function getIp4Name();
    public function isEnabled();
    public function getIp4Content();
}