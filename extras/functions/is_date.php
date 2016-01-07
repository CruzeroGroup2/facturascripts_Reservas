<?php

require_once 'boolval.php';

/**
 * @param $date
 *
 * @return bool
 */
function is_date($date) {
    $ret = false;
    if(is_string($date)) {
        $ret = boolval(strtotime($date));
    }
    return $ret;
}