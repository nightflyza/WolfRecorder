<?php

if (cfr('LIVECAMS')) {
    //catching keepalive requests
    if (ubRouting::checkGet(StreamDog::ROUTE_KEEPALIVE)) {
        $streamDog = new StreamDog();
        $streamDog->keepAlive(ubRouting::get($streamDog::ROUTE_KEEPALIVE));
        die();
    }

    //or substream keepalive requests
    if (ubRouting::checkGet(StreamDog::ROUTE_KEEPSUBALIVE)) {
        if (cfr('WALL')) {
            if ($ubillingConfig->getAlterParam(LiveCams::OPTION_WALL)) {
                $streamDog = new StreamDog();
                $streamDog->keepSubAlive(ubRouting::get($streamDog::ROUTE_KEEPSUBALIVE));
                die();
            }
        }
    }

    $liveCams = new LiveCams();
    if (ubRouting::checkGet($liveCams::ROUTE_VIEW)) {
        $channelId = ubRouting::get($liveCams::ROUTE_VIEW, 'gigasafe');
        $acl = new ACL();
        if ($acl->isMyChannel($channelId)) {
            $channelName = $liveCams->getCameraComment($channelId);
            show_window(__('Live') . ': ' . $channelName, $liveCams->renderLive($channelId));
        } else {
            log_register('LIVE FAIL CHANNEL `'.$channelId.'` ACCESS VIOLATION');
            show_error(__('Access denied'));
        }
    } else {
        //optional live wall or default list?        
        if ($ubillingConfig->getAlterParam($liveCams::OPTION_WALL)) {
            $titleControls = '';
            if (cfr('WALL')) {
                if (ubRouting::checkGet($liveCams::ROUTE_DL_PLAYLIST)) {
                    $liveCams->getLiveCamerasPlayList();
                }
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
