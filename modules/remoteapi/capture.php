<?php

if (ubRouting::get('action') == 'capture') {
    $recorder = new Recorder();
    $recorder->captureAll();
    die('OK:CAPTURE');
}