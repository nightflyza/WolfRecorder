<?php

if (cfr('EXPORT')) {
    $export = new Export();

    //viewing channel export interface
    if (ubRouting::checkGet($export::ROUTE_CHANNEL)) {
        $exportDate = (ubRouting::checkPost($export::PROUTE_DATE_EXPORT)) ? ubRouting::post($export::PROUTE_DATE_EXPORT) : curdate();
        show_window(__('Export records') . ': ' . $exportDate, $export->renderExportLookup(ubRouting::get($export::ROUTE_CHANNEL)));
    }

    //rendering channels list
    if (!ubRouting::checkGet($export::ROUTE_CHANNEL)) {
        show_window(__('Available cameras'), $export->renderCamerasList());
    }
} else {
    show_error(__('Access denied'));
}