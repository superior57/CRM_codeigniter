<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractPopupMessage;

class FirstTagCreated extends AbstractPopupMessage
{
    public function isVisible(...$params)
    {
        $tag_id = $params[0];

        return $tag_id == 1;
    }

    public function getMessage(...$params)
    {
        return 'Congrats! You created the first tags! <br /> Did you know that you can apply color to tags in Setup->Theme Style?';
    }
}
