<?php

if (ubRouting::get('action') == 'streamdog') {
    $streamDog = new StreamDog();
    $liveCams = new LiveCams();
    $allRunningStreams = $liveCams->getRunningStreams();
    if (!empty($allRunningStreams)) {
        foreach ($allRunningStreams as $eachCameraId => $eachPid) {
            //camera not in use?
            if (!$streamDog->isCameraInUse($eachCameraId)) {
                $liveCams->stopStream($eachCameraId);
            }
        }
    }
    die('OK:STREAMDOG');
}