<?php

namespace app\services\utilities;

defined('BASEPATH') or exit('No direct script access allowed');

class Arr
{
    public static function toObject($array)
    {
        if (!is_array($array) && !is_object($array)) {
            return new \stdClass();
        }

        return json_decode(json_encode((object) $array));
    }

    public static function flatten(array $array)
    {
        $return = [];
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });

        return $return;
    }

    /**
     * @see  https://www.php.net/manual/en/function.array-merge-recursive.php#92195
    * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
    * keys to arrays rather than overwriting the value in the first array with the duplicate
    * value in the second array, as array_merge does. I.e., with array_merge_recursive,
    * this happens (documented behavior):
    *
    * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
    *     => array('key' => array('org value', 'new value'));
    *
    * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
    * Matching keys' values in the second array overwrite those in the first array, as is the
    * case with array_merge, i.e.:
    *
    * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
    *     => array('key' => array('new value'));
    *
    * Parameters are passed by reference, though only for performance reasons. They're not
    * altered by this function.
    *
    * @param array $array1
    * @param array $array2
    * @return array
    * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
    * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
    */
    public static function merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = self::merge_recursive_distinct($merged [$key], $value);
            } else {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }

    public static function inMultidimensional($array, $key, $val)
    {
        foreach ($array as $item) {
            if (isset($item[$key]) && $item[$key] == $val) {
                return true;
            }
        }

        return false;
    }

    public static function pluck($array, $key)
    {
        return array_map(function ($v) use ($key) {
            return is_object($v) ? $v->$key : $v[$key];
        }, $array);
    }

    public static function valueExistsByKey($array, $key, $val)
    {
        foreach ($array as $item) {
            if (isset($item[$key]) && $item[$key] == $val) {
                return true;
            }
        }

        return false;
    }

    public static function sortBy($array, $key, $keepIndex = false)
    {
        if (!is_array($array)) {
            return [];
        }

        $func = $keepIndex ? 'usort' : 'uasort';

        $func($array, function ($a, $b) use ($key) {
            return $a[$key] - $b[$key];
        });

        return $array;
    }
}
