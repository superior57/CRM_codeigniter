<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractPopupMessage;

class FirstTicketCreated extends AbstractPopupMessage
{
    public function isVisible(...$params)
    {
        $ticket_id = $params[0];

        return $ticket_id == 1;
    }

    public function getMessage(...$params)
    {
        return 'First Ticket Created! <br /> <span style="font-size:26px;">Did you know that you can embed Ticket Form (Setup->Settings->Support->Ticket Form) directly in your websites?</span>';
    }
}
