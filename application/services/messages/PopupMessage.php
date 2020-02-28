<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractPopupMessage;

class PopupMessage
{
    private $message;

    public function __construct($message)
    {
        if (is_string($message)) {
            $message = new $message;
        }

        if (!$message instanceof AbstractPopupMessage) {
            throw new \Exception(get_class($message) . ' message must be an instance of "' . AbstractPopupMessage::fqcn() . '"');
        }

        $this->message = $message;
    }

    public function check(...$params)
    {
        if ($this->message->isVisible(...$params)) {
            $this->set(...$params);
        }
    }

    public function set(...$params)
    {
        set_system_popup($this->message->getMessage(...$params));
    }
}
