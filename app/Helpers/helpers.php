<?php

function formatNumber($num)
{
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'k';
    }
    return $num;
}

function formatSeconds($seconds)
{
    if ($seconds >= 60) {
        return round($seconds / 60) . ' Minutes';
    }
    return $seconds . ' Seconds';
}
