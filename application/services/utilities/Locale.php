<?php

namespace app\services\utilities;

defined('BASEPATH') or exit('No direct script access allowed');

class Locale
{
    public static function getByLanguage($language = 'english')
    {
        $locale = 'en';
        if ($language == '') {
            return $locale;
        }

        $locales = get_locales();

        if (isset($locales[$language])) {
            $locale = $locales[$language];
        } elseif (isset($locales[ucfirst($language)])) {
            $locale = $locales[ucfirst($language)];
        } else {
            foreach ($locales as $key => $val) {
                $key      = strtolower($key);
                $language = strtolower($language);
                if (strpos($key, $language) !== false) {
                    $locale = $val;
                // In case $language is bigger string then $key
                } elseif (strpos($language, $key) !== false) {
                    $locale = $val;
                }
            }
        }

        return $locale;
    }

    public static function getElFinderLangKey($locale)
    {
        if ($locale == 'ja') {
            $locale = 'jp';
        } elseif ($locale == 'pt') {
            $locale = 'pt_BR';
        } elseif ($locale == 'ug') {
            $locale = 'ug_CN';
        } elseif ($locale == 'zh') {
            $locale = 'zh_TW';
        }

        return $locale;
    }

    public static function getTinyMceLangKey($locale, $availableLanguages)
    {
        $lang = '';

        if ($locale == 'en') {
            return $lang;
        }

        if ($locale == 'hi') {
            return 'hi_IN';
        } elseif ($locale == 'he') {
            return 'he_IL';
        } elseif ($locale == 'sv') {
            return 'sv_SE';
        } elseif ($locale == 'sl') {
            return 'sl_SI';
        }

        foreach ($availableLanguages as $lang) {
            $_temp_lang = explode('.', $lang);
            if ($locale == $_temp_lang[0]) {
                return $locale;
            } elseif ($locale . '_' . strtoupper($locale) == $_temp_lang[0]) {
                return $locale . '_' . strtoupper($locale);
            }
        }

        return $lang;
    }

    public static function app()
    {
        return [
        'Estonian'    => 'et',
        'Arabic'      => 'ar',
        'Bulgarian'   => 'bg',
        'Catalan'     => 'ca',
        'Czech'       => 'cs',
        'Danish'      => 'da',
        'Albanian'    => 'sq',
        'German'      => 'de',
        'Deutsch'     => 'de',
        'Dutch'       => 'nl',
        'Greek'       => 'el',
        'English'     => 'en',
        'Finland'     => 'fi',
        'Spanish'     => 'es',
        'Persian'     => 'fa',
        'Finnish'     => 'fi',
        'French'      => 'fr',
        'Hebrew'      => 'he',
        'Hindi'       => 'hi',
        'Indonesian'  => 'id',
        'Hindi'       => 'hi',
        'Croatian'    => 'hr',
        'Hungarian'   => 'hu',
        'Icelandic'   => 'is',
        'Italian'     => 'it',
        'Japanese'    => 'ja',
        'Korean'      => 'ko',
        'Lithuanian'  => 'lt',
        'Latvian'     => 'lv',
        'Norwegian'   => 'nb',
        'Netherlands' => 'nl',
        'Polish'      => 'pl',
        'Portuguese'  => 'pt',
        'Romanian'    => 'ro',
        'Russian'     => 'ru',
        'Slovak'      => 'sk',
        'Slovenian'   => 'sl',
        'Serbian'     => 'sr',
        'Swedish'     => 'sv',
        'Thai'        => 'th',
        'Turkish'     => 'tr',
        'Ukrainian'   => 'uk',
        'Vietnamese'  => 'vi',
    ];
    }
}
