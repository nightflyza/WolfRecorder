<?php

if (ubRouting::get('action') == 'subswarm') {
    if (ubRouting::checkGet('cameraid')) {
        $cameraId = ubRouting::get('cameraid', 'int');
        $liveCams = new LiveCams();
        $liveCams->runSubStream($cameraId);
        die('OK:SUBSWARM');
    } else {
        die('ERROR:NO_CAMERAID');
    }
}