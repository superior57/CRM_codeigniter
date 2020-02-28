<?php

defined('BASEPATH') or exit('No direct script access allowed');

class BadUserAgentBlock
{
    public function init()
    {
        if (defined('APP_BAD_USER_AGENT_BLOCK') && APP_BAD_USER_AGENT_BLOCK) {
            include_once(__DIR__ . '/BadUserAgents.php');

            $agents = hooks()->apply_filters('bad_user_agents', $agents);

            if (in_array($_SERVER['HTTP_USER_AGENT'], $agents)) {
                $this->forbidden();
            }
        }
    }

    public static function forbidden()
    {
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol . ' 403 Forbidden');
        exit();
    }
}
