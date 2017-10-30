<?php

if (!function_exists('get_today_morning_timestamp')) {
    function get_today_morning_timestamp()
    {
        $date = get_date(time());

        return get_timestamp($date);
    }
}

if (!function_exists('get_date')) {
    function get_date($timestamp)
    {
        return date('Y-m-d', $timestamp);
    }
}

if (!function_exists('get_timestamp')) {
    function get_timestamp($date)
    {
        list($year, $month, $day) = explode('-', $date);

        return mktime(0, 0, 0, $month, $day, $year);
    }
}

if (!function_exists('starts_with')) {
    function starts_with($str, $start)
    {
        return strpos($str, $start) === 0;
    }
}
