<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractPopupMessage;

class FirstLeadCreated extends AbstractPopupMessage
{
    public function isVisible(...$params)
    {
        $lead_id = $params[0];

        return $lead_id == 1;
    }

    public function getMessage(...$params)
    {
        return 'First Lead Created! <br /> <span style="font-size:26px;">You can use Web To Lead Forms (Setup->Leads->Web To Lead) to capture leads directly from your website.</span>';
    }
}
