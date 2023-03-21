<?php

if (cfr('ROOT')) {
    error_reporting(E_ALL);
    set_time_limit(0);
    
    $recorder=new Recorder();
    debarr($recorder);
} else {
    show_error(__('Access denied'));
}