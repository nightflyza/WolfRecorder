<?php

if (ubRouting::get('action') == 'liveswarm') {
    if (ubRouting::checkGet('cameraid')) {
        $cameraId = ubRouting::get('cameraid', 'int');
        $liveCams = new LiveCams();
        $liveCams->runStream($cameraId);
        die('OK:LIVESWARM');
    } else {
        die('ERROR:NO_CAMERAID');
    }
}