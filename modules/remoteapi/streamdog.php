<?php

if (ubRouting::get('action') == 'streamdog') {
    $streamDog = new StreamDog();
    $liveCams = new LiveCams();

    //running main live-streams processing
    $allRunningStreams = $liveCams->getRunningStreams();
    if (!empty($allRunningStreams)) {
        foreach ($allRunningStreams as $eachCameraId => $eachPid) {
            //camera not in use?
            if (!$streamDog->isCameraInUse($eachCameraId)) {
                $liveCams->stopStream($eachCameraId);
                //log_register('STREAMDOG LIVE ['.$eachCameraId.'] STOPPED NOT IN USE IN '.$streamDog::TIMEOUT);
            }
        }
    }
    
    //running sub-streams processing
    $allRunningSubStreams=$liveCams->getRunningSubStreams();
    if (!empty($allRunningSubStreams)) {
        foreach ($allRunningSubStreams as $eachCameraId=>$eachPid) {
            if (!$streamDog->isCameraSubInUse($eachCameraId)) {
                $liveCams->stopSubStream($eachCameraId);
                log_register('STREAMDOG SUB ['.$eachCameraId.'] STOPPED NOT IN USE IN '.$streamDog::SUB_TIMEOUT);
            }
        }
    }
    die('OK:STREAMDOG');
}