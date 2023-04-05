<?php

if (cfr('EXPORT')) {
    $export = new Export();

    //viewing channel export interface
    if (ubRouting::checkGet($export::ROUTE_CHANNEL)) {
        //deleting record
        if (ubRouting::checkGet($export::ROUTE_DELETE)) {
            $deletionResult = $export->deleteRecording(ubRouting::get($export::ROUTE_DELETE));
            if (empty($deletionResult)) {
                ubRouting::nav($export::URL_ME . '&' . $export::ROUTE_CHANNEL . '=' . ubRouting::get($export::ROUTE_CHANNEL));
            } else {
                show_error($deletionResult);
            }
        }

        //run export if required
        if (ubRouting::checkPost(array($export::PROUTE_DATE_EXPORT, $export::PROUTE_TIME_FROM, $export::PROUTE_TIME_TO))) {
            $exportChannel = ubRouting::get($export::ROUTE_CHANNEL);
            $exportDate = ubRouting::post($export::PROUTE_DATE_EXPORT);
            $exportTimeFrom = ubRouting::post($export::PROUTE_TIME_FROM);
            $exportTimeTo = ubRouting::post($export::PROUTE_TIME_TO);
            $exportResult = $export->runExport($exportChannel, $exportDate, $exportTimeFrom, $exportTimeTo);
            if (!empty($exportResult)) {
                show_error($exportResult);
            }
        }


        //export interface here
        $exportDate = (ubRouting::checkPost($export::PROUTE_DATE_EXPORT)) ? ubRouting::post($export::PROUTE_DATE_EXPORT) : curdate();
        show_window(__('Export records') . ': ' . $exportDate, $export->renderExportLookup(ubRouting::get($export::ROUTE_CHANNEL)));

        //already saved records here
        show_window(__('Your saved records'), $export->renderAvailableRecords(ubRouting::get($export::ROUTE_CHANNEL)));
    }

    //rendering channels list
    if (!ubRouting::checkGet($export::ROUTE_CHANNEL)) {
        show_window(__('Available cameras'), $export->renderCamerasList());
    }
} else {
    show_error(__('Access denied'));
}