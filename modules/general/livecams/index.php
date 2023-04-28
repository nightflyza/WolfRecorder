<?php

if (cfr('LIVECAMS')) {
    $liveCams = new LiveCams();

    if (ubRouting::checkGet($liveCams::ROUTE_VIEW)) {
        $acl = new ACL();
        if ($acl->isMyChannel(ubRouting::get($liveCams::ROUTE_VIEW))) {
            $channelName = $liveCams->getCameraComment(ubRouting::get($liveCams::ROUTE_VIEW));
            show_window(__('Live') . ': ' . $channelName, $liveCams->renderLive(ubRouting::get($liveCams::ROUTE_VIEW)));
        } else {
            show_error(__('Access denied'));
        }
    } else {
        show_window(__('My cameras'), $liveCams->renderList());
        wr_Stats();
    }
} else {
    show_error(__('Access denied'));
}