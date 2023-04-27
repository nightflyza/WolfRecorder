<?php

if (cfr('EXPORT')) {
    $export = new Export();

    //viewing channel export interface
    if (ubRouting::checkGet($export::ROUTE_CHANNEL)) {
        $acl = new ACL();
        if ($acl->isMyChannel(ubRouting::get($export::ROUTE_CHANNEL))) {
            //deleting record
            if (ubRouting::checkGet($export::ROUTE_DELETE)) {
                $deletionResult = $export->deleteRecording(ubRouting::get($export::ROUTE_DELETE));
                if (empty($deletionResult)) {
                    ubRouting::nav($export::URL_ME . '&' . $export::ROUTE_CHANNEL . '=' . ubRouting::get($export::ROUTE_CHANNEL));
                } else {
                    show_error($deletionResult);
                }
            }

            //show scheduling confirmation notification
            if (ubRouting::checkGet(array($export::ROUTE_SCHED_OK, $export::ROUTE_CHANNEL))) {
                show_window('', $export->renderExportScheduledNotify(ubRouting::get($export::ROUTE_CHANNEL)));
            }

            //run export if required
            if (ubRouting::checkPost(array($export::PROUTE_DATE_EXPORT, $export::PROUTE_TIME_FROM, $export::PROUTE_TIME_TO))) {
                $exportChannel = ubRouting::get($export::ROUTE_CHANNEL);
                $exportDate = ubRouting::post($export::PROUTE_DATE_EXPORT);
                $exportTimeFrom = ubRouting::post($export::PROUTE_TIME_FROM);
                $exportTimeTo = ubRouting::post($export::PROUTE_TIME_TO);
                $exportRequestResult = $export->requestExport($exportChannel, $exportDate, $exportTimeFrom, $exportTimeTo);
                if (!empty($exportRequestResult)) {
                    show_error($exportRequestResult);
                } else {
                    //redirect to success scheduling confirmation
                    ubRouting::nav($export::URL_ME . '&' . $export::ROUTE_CHANNEL . '=' . $exportChannel . '&' . $export::ROUTE_SCHED_OK . '=true');
                }
            }


            //export interface here
            $channelName = $export->getCameraComment(ubRouting::get($export::ROUTE_CHANNEL));
            show_window(__('Save records') . ': ' . $channelName, $export->renderExportLookup(ubRouting::get($export::ROUTE_CHANNEL)));

            //rendering schedule if not empty
            $exportSchedule = $export->renderScheduledExports();
            if ($exportSchedule) {
                show_window(__('Your scheduled records saving'), $exportSchedule);
            }

            //already saved records here
            show_window(__('Your saved records'), $export->renderAvailableRecords(ubRouting::get($export::ROUTE_CHANNEL)));
        } else {
            show_error(__('Access denied'));
        }
    }

    //rendering channels list
    if (!ubRouting::checkGet($export::ROUTE_CHANNEL)) {
        show_window(__('Available cameras'), $export->renderCamerasList());
    }
} else {
    show_error(__('Access denied'));
}