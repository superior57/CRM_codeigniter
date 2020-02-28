<?php

namespace app\services\utilities;

defined('BASEPATH') or exit('No direct script access allowed');

trait StrClickable
{
    /**
     * Callback for clickable
     */
    protected static function make_url_clickable_cb($matches)
    {
        $ret = '';
        $url = $matches[2];
        if (empty($url)) {
            return $matches[0];
        }
        // removed trailing [.,;:] from URL
        if (in_array(substr($url, -1), [
        '.',
        ',',
        ';',
        ':',
    ]) === true) {
            $ret = substr($url, -1);
            $url = substr($url, 0, strlen($url) - 1);
        }

        return $matches[1] . "<a href=\"$url\" rel=\"nofollow\" target='_blank'>$url</a>" . $ret;
    }

    /**
     * Callback for clickable
     */
    protected static function make_web_ftp_clickable_cb($matches)
    {
        $ret  = '';
        $dest = $matches[2];
        $dest = 'http://' . $dest;
        if (empty($dest)) {
            return $matches[0];
        }
        // removed trailing [,;:] from URL
        if (in_array(substr($dest, -1), [
        '.',
        ',',
        ';',
        ':',
    ]) === true) {
            $ret  = substr($dest, -1);
            $dest = substr($dest, 0, strlen($dest) - 1);
        }

        return $matches[1] . "<a href=\"$dest\" rel=\"nofollow\" target='_blank'>$dest</a>" . $ret;
    }

    /**
     * Callback for clickable
     */
    protected static function make_email_clickable_cb($matches)
    {
        $email = $matches[2] . '@' . $matches[3];

        return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
    }

    /**
     * Check for links/emails/ftp in string to wrap in href
     * @param  string $ret
     * @return string      formatted string with href in any found
     */
    public static function clickable($ret)
    {
        $ret = ' ' . $ret;
        // in testing, using arrays here was found to be faster
        $ret = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', 'self::make_url_clickable_cb', $ret);
        $ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', 'self::make_web_ftp_clickable_cb', $ret);
        $ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', 'self::make_email_clickable_cb', $ret);
        // this one is not in an array because we need it to run last, for cleanup of accidental links within links
        $ret = preg_replace('#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', '$1$3</a>', $ret);
        $ret = trim($ret);

        return $ret;
    }
}
