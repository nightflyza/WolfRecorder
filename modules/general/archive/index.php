<?php

if (cfr('Archive')) {
    $archive = new Archive();

    //archive lookup
    if (ubRouting::checkGet($archive::ROUTE_VIEW)) {
        show_window(__('Archive'), $archive->renderLookup(ubRouting::get($archive::ROUTE_VIEW)));
    }

    //cameras list
    if (!ubRouting::checkGet($archive::ROUTE_VIEW)) {
        show_window(__('Available cameras'), $archive->renderCamerasList());
    }
} else {
    show_error(__('Access denied'));
}