<?php

if (cfr('EXPORT')) {
    $export = new Export();

    //viewing channel export interface
    if (ubRouting::checkGet($export::ROUTE_CHANNEL)) {
        
    }

    //rendering channels list
    if (!ubRouting::checkGet($export::ROUTE_CHANNEL)) {
        show_window(__('Available cameras'), $export->renderCamerasList());
    }
} else {
    show_error(__('Access denied'));
}