<?php

namespace app\services;

defined('BASEPATH') or exit('No direct script access allowed');

class Timezones
{
    private static $list = null;

    public static function get()
    {
        static::set();

        return self::$list;
    }

    private static function set()
    {
        if (is_null(static::$list)) {
            static::$list = [
                'EUROPE'     => \DateTimeZone::listIdentifiers(\DateTimeZone::EUROPE),
                'AMERICA'    => \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA),
                'INDIAN'     => \DateTimeZone::listIdentifiers(\DateTimeZone::INDIAN),
                'AUSTRALIA'  => \DateTimeZone::listIdentifiers(\DateTimeZone::AUSTRALIA),
                'ASIA'       => \DateTimeZone::listIdentifiers(\DateTimeZone::ASIA),
                'AFRICA'     => \DateTimeZone::listIdentifiers(\DateTimeZone::AFRICA),
                'ANTARCTICA' => \DateTimeZone::listIdentifiers(\DateTimeZone::ANTARCTICA),
                'ARCTIC'     => \DateTimeZone::listIdentifiers(\DateTimeZone::ARCTIC),
                'ATLANTIC'   => \DateTimeZone::listIdentifiers(\DateTimeZone::ATLANTIC),
                'PACIFIC'    => \DateTimeZone::listIdentifiers(\DateTimeZone::PACIFIC),
                'UTC'        => \DateTimeZone::listIdentifiers(\DateTimeZone::UTC),
            ];
        }
    }
}
