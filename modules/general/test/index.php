<?php

if (cfr('ROOT')) {
    error_reporting(E_ALL);
    set_time_limit(0);

    $recorder = new Recorder();

    debarr($recorder->runRecord(2));
    debarr($recorder->runRecord(3));
      debarr($recorder->runRecord(4));
} else {
    show_error(__('Access denied'));
}