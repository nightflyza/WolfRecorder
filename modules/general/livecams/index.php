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
        if (cfr('WALL')) {
            if ($ubillingConfig->getAlterParam(LiveCams::OPTION_WALL)) {
                $streamDog->keepSubAlive(ubRouting::get($streamDog::ROUTE_KEEPSUBALIVE));
                die();
            }
        }
    }

    $liveCams = new LiveCams();
    if (ubRouting::checkGet($liveCams::ROUTE_VIEW)) {
        $channelId = ubRouting::get($liveCams::ROUTE_VIEW, 'gigasafe');
        $acl = new ACL();
        if ($acl->isMyChannel(ubRouting::get($channelId))) {
            $channelName = $liveCams->getCameraComment($channelId);
            show_window(__('Live') . ': ' . $channelName, $liveCams->renderLive($channelId));
        } else {
            show_error(__('Access denied'));
        }
    } else {
        //optional live wall or default list?        
        if ($ubillingConfig->getAlterParam($liveCams::OPTION_WALL)) {
            $titleControls = '';
            if (cfr('WALL')) {
                $titleControls = $liveCams->getTitleControls();
            }
            if (ubRouting::checkGet($liveCams::ROUTE_LIVEWALL)) {
                if (cfr('WALL')) {
                    show_window(__('My cameras') . ' ' . $titleControls, $liveCams->renderLiveWall());
                } else {
                    show_error(__('Access denied'));
                }
            } else {
                show_window(__('My cameras') . ' ' . $titleControls, $liveCams->renderList());
            }
        } else {
            //just default live cameras list
            show_window(__('My cameras'), $liveCams->renderList());
        }

        wr_Stats();
    }
} else {
    show_error(__('Access denied'));
}
