<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

abstract class AbstractPopupMessage
{
    abstract public function isVisible(...$params);

    abstract public function getMessage(...$params);

    public static function fqcn()
    {
        return self::class;
    }
}
