<?php

if (cfr('EXPORT')) {
    $export = new Export();
    //some record deletion?
    //deleting record
    if (ubRouting::checkGet($export::ROUTE_DELETE)) {
        $deletionResult = $export->deleteRecording(ubRouting::get($export::ROUTE_DELETE));
        if (empty($deletionResult)) {
            ubRouting::nav($export::URL_RECORDS);
        } else {
            show_error($deletionResult);
        }
    }

    //already saved records here
    show_window(__('Your saved records'), $export->renderAvailableRecords());
} else {
    show_error(__('Access denied'));
}