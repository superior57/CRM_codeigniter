<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractMessage;

class Message
{
    private $message;

    public function __construct($message)
    {
        if (is_string($message)) {
            $message = new $message;
        }

        if (!$message instanceof AbstractMessage) {
            throw new \Exception(get_class($message) . ' message must be an instance of "' . AbstractMessage::fqcn() . '"');
        }

        $this->message = $message;
    }

    public function check()
    {
        if ($this->message->isVisible()) {
            $this->show();
        }
    }

    public function show()
    {
        echo $this->message->openHtml();
        echo $this->message->getMessage();
        echo $this->message->closeHtml();
    }
}
