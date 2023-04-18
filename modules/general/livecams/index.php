<?php

if (cfr('LIVECAMS')) {
    $liveCams=new LiveCams();
    show_window(__('My cameras'), $liveCams->renderList());
} else {
    show_error(__('Access denied'));
}