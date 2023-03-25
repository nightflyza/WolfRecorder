<?php

if (ubRouting::get('action') == 'rotator') {
    $rotator = new Rotator();
    $rotator->run();
    die('OK:ROTATOR');
}