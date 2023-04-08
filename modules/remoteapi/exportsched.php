<?php

/**
 * Export schedule
 */
if (ubRouting::get('action') == 'exportsched') {
    $scheduleProcess = new StarDust(Export::PID_SCHEDULE);
    if ($scheduleProcess->notRunning()) {
        $scheduleProcess->start();
        $export = new Export();
        $export->scheduleRun();
        $scheduleProcess->stop();
        die('OK:EXPORTSCHED');
    } else {
        die('SKIP:EXPORTSCHED_ALREADY_RUNNING');
    }
}