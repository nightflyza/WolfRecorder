<?php

if (cfr('ROOT')) {
    error_reporting(E_ALL);
    set_time_limit(0);
    
    
    $rotator=new Rotator();
    $rotator->run();
} else {
    show_error(__('Access denied'));
}