<?php

if (cfr('LIVECAMS')) {
    //catching keepalive requests
    $streamDog = new StreamDog();
    if (ubRouting::checkGet($streamDog::ROUTE_KEEPALIVE)) {
        $streamDog->keepAlive(ubRouting::get($streamDog::ROUTE_KEEPALIVE));
        die();
    }

    //or substream keepalive requests
    if (ubRouting::checkGet($streamDog::ROUTE_KEEPSUBALIVE)) {
        $streamDog->keepSubAlive(ubRouting::get($streamDog::ROUTE_KEEPSUBALIVE));
        die();
    }

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
        if (ubRouting::checkGet('wall')) {
            show_window(__('My cameras'), $liveCams->renderLiveWall());
        } else {
            show_window(__('My cameras'), $liveCams->renderList());
        }

        wr_Stats();
    }
} else {
    show_error(__('Access denied'));
}