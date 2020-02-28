<?php

namespace app\services\utilities;

defined('BASEPATH') or exit('No direct script access allowed');

use Cocur\Slugify\RuleProvider\DefaultRuleProvider as DefaultSlugRuleProvider;
use Cocur\Slugify\Slugify;
use app\services\utilities\StrClickable as Clickable;

class Str
{
    use Clickable;

    public static function startsWith($haystack, $needle)
    {
        return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    public static function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    public static function isHtml($string)
    {
        return preg_match('/<[^<]+>/', $string, $m) != 0;
    }

    public static function after($string, $substring)
    {
        $pos = strpos($string, $substring);
        if ($pos === false) {
            return $string;
        }

        return (substr($string, $pos + strlen($substring)));
    }

    public static function before($string, $substring)
    {
        $pos = strpos($string, $substring);
        if ($pos === false) {
            return $string;
        }

        return (substr($string, 0, $pos));
    }

    public static function replaceLast($search, $replace, $subject)
    {
        $pos = strrpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    public static function between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini    = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;

        return substr($string, $ini, $len);
    }

    public static function similarity($str1, $str2)
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        $max        = max($len1, $len2);
        $similarity = $i = $j = 0;

        while (($i < $len1) && isset($str2[$j])) {
            if ($str1[$i] == $str2[$j]) {
                $similarity++;
                $i++;
                $j++;
            } elseif ($len1 < $len2) {
                $len1++;
                $j++;
            } elseif ($len1 > $len2) {
                $i++;
                $len1--;
            } else {
                $i++;
                $j++;
            }
        }

        return round($similarity / $max, 2);
    }

    public static function slug($str, $options = [])
    {
        $defaults = [];

        // Deprecated
        if (isset($options['delimiter'])) {
            $defaults['separator'] = $options['delimiter'];
            unset($options['delimiter']);
        }

        $m = new DefaultSlugRuleProvider();

        $lang = isset($options['lang']) ? $options['lang'] : 'english';
        $set  = $lang == 'english' ? 'default' : $lang;

        $default_active_rule_sets = [
            'default',
            'azerbaijani',
            'burmese',
            'hindi',
            'georgian',
            'norwegian',
            'vietnamese',
            'ukrainian',
            'latvian',
            'finnish',
            'greek',
            'czech',
            'arabic',
            'turkish',
            'polish',
            'german',
            'russian',
            'romanian',
        ];

        // Set for portuguese in Slugify is named portuguese-brazil
        if ($set == 'portuguese_br' || $set == 'portuguese') {
            $set = 'portuguese-brazil';
        }

        if (!in_array($set, $default_active_rule_sets)) {
            $r = @$m->getRules($set);
            // Check if set exist
            if ($r) {
                $defaults['rulesets'] = [$set];
            }
        }

        $options = array_merge($defaults, $options);

        $slugify = new Slugify($options);

        return $slugify->slugify($str);
    }
}
