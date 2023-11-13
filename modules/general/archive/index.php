<?php

if (cfr('ARCHIVE')) {
    $archive = new Archive();
    //neura search ajax background
    if ($ubillingConfig->getAlterParam('NEURAL_ENABLED')) {
        if (ubRouting::checkGet(NeuralObjSearch::ROUTE_CHAN_DETECT)) {
            $acl = new ACL();
            if ($acl->isMyChannel(ubRouting::get(NeuralObjSearch::ROUTE_CHAN_DETECT))) {
                $neuraObj = new NeuralObjSearch();
                $neuraObj->renderObjectDetector(ubRouting::get(NeuralObjSearch::ROUTE_CHAN_DETECT), ubRouting::get(NeuralObjSearch::ROUTE_DATE));
            } else {
                $messages = new UbillingMessageHelper();
                die($messages->getStyledMessage(__('Access denied'), 'error') . wf_delimiter());
            }
        }
    }

    //archive lookup by channel ID
    if (ubRouting::checkGet($archive::ROUTE_VIEW)) {
        $acl = new ACL();
        if ($acl->isMyChannel(ubRouting::get($archive::ROUTE_VIEW))) {
            $channelName = $archive->getCameraComment(ubRouting::get($archive::ROUTE_VIEW));
            show_window(__('View') . ': ' . $channelName, $archive->renderLookup(ubRouting::get($archive::ROUTE_VIEW)));
        } else {
            show_error(__('Access denied'));
        }
    }

    //cameras list
    if (!ubRouting::checkGet($archive::ROUTE_VIEW)) {
        show_window(__('Available cameras'), $archive->renderCamerasList());
        wr_Stats();
    }
} else {
    show_error(__('Access denied'));
}