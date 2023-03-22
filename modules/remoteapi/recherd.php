<?php

if (ubRouting::get('action') == 'recherd') {
    if (ubRouting::checkGet('cameraid')) {
        /**
         * .------------------------.
         * |\\////////      120 min |
         * | \/  __  ______  __     |
         * |    /  \|\.....|/  \    |
         * |    \__/|/_____|\__/    |
         * | VHS                    |
         * |    ________________    |
         * |___/_._o________o_._\___|
         *
         */
        $cameraId = ubRouting::get('cameraid', 'int');
        $recorder = new Recorder();
        $recorder->runRecord($cameraId);
        die('OK:RECHERD');
    } else {
        die('ERROR:NO_CAMERAID');
    }
}