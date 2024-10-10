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
        if (ubRouting::checkPost($export::PROUTE_MODET_RUN)) {
            if ($ubillingConfig->getAlterParam(MoDet::OPTION_ENABLE)) {
                $motionDetector = new MoDet();
                $motionThreshold = $export->getMoDetParamSensitivity(ubRouting::post($export::PROUTE_MODET_SENS));
                $motionTimeScale = $export->getMoDetParamTimeScale(ubRouting::post($export::PROUTE_MODET_TIMESCALE));
                $motionResult = $motionDetector->runMotionFiltering(ubRouting::post($export::PROUTE_MODET_RUN), $motionThreshold, $motionTimeScale);
                if (empty($motionResult)) {
                    show_window('', $motionDetector->renderScheduledNotify());
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
