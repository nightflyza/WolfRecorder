<?php

if (cfr('EXPORT')) {
    $export = new Export();
    //already saved records here
    show_window(__('Your saved records'), $export->renderAvailableRecords(ubRouting::get($export::ROUTE_CHANNEL)));
} else {
    show_error(__('Access denied'));
}