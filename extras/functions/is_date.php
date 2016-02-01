<?php

require_once 'boolval.php';

/**
 * @param $date
 *
 * @return bool
 */
function is_date($date) {
    $ret = false;
    if(is_string($date) && $date != '0000-00-00 00:00:00') {
        $ret = boolval(strtotime($date));
    }
    return $ret;
}