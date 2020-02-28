<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

abstract class AbstractMessage
{
    protected $alertClass = 'danger';

    abstract public function isVisible();

    abstract public function getMessage();

    public function openHtml()
    {
        return '<div class="col-md-12">
            <div class="alert alert-' . $this->alertClass . '" font-medium>';
    }

    public function closeHtml()
    {
        return '</div>
			</div>';
    }

    public static function fqcn()
    {
        return self::class;
    }
}
