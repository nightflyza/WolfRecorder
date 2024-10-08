<?php

if (cfr('EXPORT')) {
    $export = new Export();
    //some record deletion?
    if (ubRouting::checkGet($export::ROUTE_DELETE)) {
        $deletionResult = $export->deleteRecording(ubRouting::get($export::ROUTE_DELETE));
        if (empty($deletionResult)) {
            ubRouting::nav($export::URL_RECORDS);
        } else {
            show_error($deletionResult);
        }
    }

    //existing record preview
    if (ubRouting::checkGet($export::ROUTE_PREVIEW)) {
        show_window(__('Preview'), $export->renderRecordPreview(ubRouting::get($export::ROUTE_PREVIEW)));
    } else {
        //rendering schedule if not empty
        $exportSchedule = $export->renderScheduledExports();
        if ($exportSchedule) {
            show_window(__('Your scheduled records saving'), $exportSchedule);
        }

        //motion detection handling
        if (ubRouting::checkGet($export::ROUTE_MODET)) {
            if ($ubillingConfig->getAlterParam(MoDet::OPTION_ENABLE)) {
                $motionDetector = new MoDet();
                $motionResult = $motionDetector->runMotionFiltering(ubRouting::get($export::ROUTE_MODET));
                if (empty($motionResult)) {
                    show_window('',$motionDetector->renderScheduledNotify());
                } else {
                    show_error($motionResult);
                }
            }
        }

        //already saved records here
        show_window(__('Your saved records'), $export->renderAvailableRecords());
    }
} else {
    show_error(__('Access denied'));
}
