<?php

namespace app\services\upgrade;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\upgrade\UpgradeAdapter;

@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', 240);

class UpgradeCore
{
    private $adapter;

    public function __construct(CoreInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function perform()
    {
        return $this->adapter->perform($this->adapter->getConfig()->zipFile);
    }

    public function getAdapter()
    {
        return $this->adapter;
    }
}
