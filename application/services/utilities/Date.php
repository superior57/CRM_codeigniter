<?php

namespace app\services\utilities;

defined('BASEPATH') or exit('No direct script access allowed');

class Date
{
    public static function weekdaysBetweenDates($start_time, $end_time)
    {
        $interval   = new \DateInterval('P1D');
        $end_time   = $end_time->modify('+1 day');
        $dateRange  = new \DatePeriod($start_time, $interval, $end_time);
        $weekNumber = 1;
        $weeks      = [];

        foreach ($dateRange as $date) {
            $weeks[$weekNumber][] = $date->format('Y-m-d');
            if ($date->format('w') == 0) {
                $weekNumber++;
            }
        }

        return $weeks;
    }

    public static function splitWeeksChartLabel($weeks, $week)
    {
        $week_start = $weeks[$week][0];
        end($weeks[$week]);
        $key      = key($weeks[$week]);
        $week_end = $weeks[$week][$key];

        $week_start_year = date('Y', strtotime($week_start));
        $week_end_year   = date('Y', strtotime($week_end));

        $week_start_month = date('m', strtotime($week_start));
        $week_end_month   = date('m', strtotime($week_end));

        $label = '';

        $label .= date('d', strtotime($week_start));

        if ($week_start_month != $week_end_month && $week_start_year == $week_end_year) {
            $label .= ' ' . _l(date('F', mktime(0, 0, 0, $week_start_month, 1)));
        }

        if ($week_start_year != $week_end_year) {
            $label .= ' ' . _l(date('F', mktime(0, 0, 0, date('m', strtotime($week_start)), 1))) . ' ' . date('Y', strtotime($week_start));
        }

        $label .= ' - ';
        $label .= date('d', strtotime($week_end));
        if ($week_start_year != $week_end_year) {
            $label .= ' ' . _l(date('F', mktime(0, 0, 0, date('m', strtotime($week_end)), 1))) . ' ' . date('Y', strtotime($week_end));
        }

        if ($week_start_year == $week_end_year) {
            $label .= ' ' . _l(date('F', mktime(0, 0, 0, date('m', strtotime($week_end)), 1)));
            $label .= ' ' . date('Y', strtotime($week_start));
        }

        return $label;
    }

    public static function timeAgoString($date, $localization = [])
    {
        $defaultLocalization['time_ago_just_now']  = 'Just now';
        $defaultLocalization['time_ago_minute']    = 'one minute ago';
        $defaultLocalization['time_ago_minutes']   = '%s minutes ago';
        $defaultLocalization['time_ago_hour']      = 'an hour ago';
        $defaultLocalization['time_ago_hours']     = '%s hrs ago';
        $defaultLocalization['time_ago_yesterday'] = 'yesterday';
        $defaultLocalization['time_ago_days']      = '%s days ago';
        $defaultLocalization['time_ago_week']      = 'a week ago';
        $defaultLocalization['time_ago_weeks']     = '%s weeks ago';
        $defaultLocalization['time_ago_month']     = 'a month ago';
        $defaultLocalization['time_ago_months']    = '%s months ago';
        $defaultLocalization['time_ago_year']      = 'one year ago';
        $defaultLocalization['time_ago_years']     = '%s years ago';

        $localization = array_merge($defaultLocalization, $localization);

        $time_ago     = strtotime($date);
        $cur_time     = time();
        $time_elapsed = $cur_time - $time_ago;
        $seconds      = $time_elapsed;
        $minutes      = round($time_elapsed / 60);
        $hours        = round($time_elapsed / 3600);
        $days         = round($time_elapsed / 86400);
        $weeks        = round($time_elapsed / 604800);
        $months       = round($time_elapsed / 2600640);
        $years        = round($time_elapsed / 31207680);

        // Seconds
        if ($seconds <= 60) {
            return $localization['time_ago_just_now'];
        }

        //Minutes
        elseif ($minutes <= 60) {
            if ($minutes == 1) {
                return $localization['time_ago_minute'];
            }

            return sprintf($localization['time_ago_minutes'], $minutes);
        }
        //Hours
        elseif ($hours <= 24) {
            if ($hours == 1) {
                return $localization['time_ago_hour'];
            }

            return sprintf($localization['time_ago_hours'], $hours);
        }
        //Days
        elseif ($days <= 7) {
            if ($days == 1) {
                return $localization['time_ago_yesterday'];
            }

            return sprintf($localization['time_ago_days'], $days);
        }
        //Weeks
        elseif ($weeks <= 4.3) {
            if ($weeks == 1) {
                return $localization['time_ago_week'];
            }

            return sprintf($localization['time_ago_weeks'], $weeks);
        }
        //Months
        elseif ($months <= 12) {
            if ($months == 1) {
                return $localization['time_ago_month'];
            }

            return sprintf($localization['time_ago_months'], $months);
        }

        //Years
        if ($years == 1) {
            return $localization['time_ago_year'];
        }

        return sprintf($localization['time_ago_years'], $years);
    }

    public static function timeAgo($date, $from = 'now')
    {
        $datetime   = strtotime($from);
        $date2      = strtotime('' . $date);
        $holdtotsec = $datetime - $date2;
        $holdtotmin = ($datetime - $date2) / 60;
        $holdtothr  = ($datetime - $date2) / 3600;
        $holdtotday = intval(($datetime - $date2) / 86400);
        $str        = '';
        if (0 < $holdtotday) {
            $str .= $holdtotday . 'd ';
        }
        $holdhr = intval($holdtothr - $holdtotday * 24);
        $str .= $holdhr . 'h ';
        $holdmr = intval($holdtotmin - ($holdhr * 60 + $holdtotday * 1440));
        $str .= $holdmr . 'm';

        return $str;
    }
}
