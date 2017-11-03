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
        $dates = explode('-', $date);

        return mktime(0, 0, 0, $dates[1], $dates[2], $dates[0]);
    }
}

if (!function_exists('starts_with')) {
    function starts_with($str, $start)
    {
        return strpos($str, $start) === 0;
    }
}
