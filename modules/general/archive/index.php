<?php

if (cfr('ARCHIVE')) {
    $archive = new Archive();

    //archive lookup by channel ID
    if (ubRouting::checkGet($archive::ROUTE_VIEW)) {
        $channelName = $archive->getCameraComment(ubRouting::get($archive::ROUTE_VIEW));
        show_window(__('Archive') . ': ' . $channelName, $archive->renderLookup(ubRouting::get($archive::ROUTE_VIEW)));
    }

    //cameras list
    if (!ubRouting::checkGet($archive::ROUTE_VIEW)) {
        show_window(__('Available cameras'), $archive->renderCamerasList());
        wr_Stats();
    }
} else {
    show_error(__('Access denied'));
}