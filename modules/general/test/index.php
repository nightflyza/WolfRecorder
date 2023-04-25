<?php

if (cfr('ROOT')) {
    error_reporting(E_ALL);
    set_time_limit(0);
    
    
} else {
    show_error(__('Access denied'));
}