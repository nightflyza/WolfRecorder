<?php

if (ubRouting::checkGet('action') == 'modet') {
    if ($ubillingConfig->getAlterParam(MoDet::OPTION_ENABLE)) {
        if (ubRouting::checkGet('mdfp')) {
            $filePathEnc = ubRouting::get('mdfp', 'mres');
            $motionDetector = new MoDet();
            $motionDetector->startMotionFilteringProcess($filePathEnc);
            die('OK:MODET');
        }
    } else {
        die('ERROR:MODET_DISABLED');
    }
}
