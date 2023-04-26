<?php

if (cfr('LIVECAMS')) {
    $liveCams=new LiveCams();
    show_window(__('My cameras'), $liveCams->renderList());
    wr_Stats();
} else {
    show_error(__('Access denied'));
}