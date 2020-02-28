<?php

namespace app\services\utilities;

defined('BASEPATH') or exit('No direct script access allowed');

class Format
{
    public static function sec2qty($sec)
    {
        $seconds = $sec / 3600;

        return round($seconds, 2);
    }

    public static function secondsToTime($seconds = 0, $include_seconds = false)
    {
        $hours = floor($seconds / 3600);
        $mins  = floor(($seconds - ($hours * 3600)) / 60);
        $secs  = floor($seconds % 60);

        $hours   = ($hours < 10) ? '0' . $hours : $hours;
        $mins    = ($mins < 10) ? '0' . $mins : $mins;
        $secs    = ($secs < 10) ? '0' . $secs : $secs;
        $sprintF = $include_seconds == true ? '%02d:%02d:%02d' : '%02d:%02d';

        return sprintf($sprintF, $hours, $mins, $secs);
    }

    public static function hoursToSeconds($hours)
    {
        if (strpos($hours, '.') !== false) {
            $hours = str_replace('.', ':', $hours);
        }
        $tmp             = explode(':', $hours);
        $hours           = $tmp[0];
        $minutesFromHour = isset($tmp[1]) ? $tmp[1] : 0;

        return $hours * 3600 + $minutesFromHour * 60;
    }
}
