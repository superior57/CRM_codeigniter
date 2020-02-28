<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractMessage;

class Timezone extends AbstractMessage
{
    private $timezonesList = [];

    public function __construct()
    {
        $this->timezonesList = array_flatten(get_timezones_list());
    }

    public function isVisible()
    {
        return get_option('default_timezone') == '' || !in_array(get_option('default_timezone'), $this->timezonesList);
    }

    public function getMessage()
    {
        $html = '';
        if (get_option('default_timezone') == '') {
            $html .= '<strong>Default timezone not set. Navigate to Setup->Settings->Localization to set default system timezone.</strong>';
        } else {
            if (!in_array(get_option('default_timezone'), $this->timezonesList)) {
                $html .= '<strong>We updated the timezone logic for the app. Seems like your previous timezone do not fit with the new logic. Navigate to Setup->Settings->Localization to set new proper timezone.</strong>';
            }
        }

        return $html;
    }
}
