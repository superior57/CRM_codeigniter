<?php

namespace app\services\upgrade;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\upgrade\Config;

interface CoreInterface
{
    public function perform($zipFile);

    public function setConfig(Config $config);

    public function getConfig();
}
