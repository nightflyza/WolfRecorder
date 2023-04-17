<?php

/**
 * Periodic channels screenshots
 */
if (ubRouting::get('action') == 'chanshots') {
    $chanshotsProcess = new StarDust(ChanShots::CHANSHOTS_PID);
    if ($chanshotsProcess->notRunning()) {
        $chanShots = new ChanShots();
        $chanShots->run();
        die('OK:CHANSHOTS');
    } else {
        die('SKIP:CHANSHOTS_ALREADY_RUNNING');
    }
}