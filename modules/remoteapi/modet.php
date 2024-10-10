<?php

if (ubRouting::checkGet('action') == 'modet') {
    if ($ubillingConfig->getAlterParam(MoDet::OPTION_ENABLE)) {
        if (ubRouting::checkGet('mdfp')) {
            $filePathEnc = ubRouting::get('mdfp', 'mres');
            $motionDetector = new MoDet();
            $threshold = ubRouting::get('th', 'int');
            $timeScale = ubRouting::get('ts', 'int');
            $motionDetector->startMotionFilteringProcess($filePathEnc, $threshold, $timeScale);
            die('OK:MODET');
        }
    } else {
        die('ERROR:MODET_DISABLED');
    }
}
