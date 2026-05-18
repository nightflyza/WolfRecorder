<?php

if (ubRouting::get(LiveCams::ROUTE_PSEUDOLIVE)) {
    $channelId = ubRouting::get(LiveCams::ROUTE_PSEUDOLIVE);

    if (!empty($channelId)) {
        $liveCams = new LiveCams();
        $playlistBody = $liveCams->getPseudoStream($channelId);
        print($playlistBody);
    }
} else {
    if (ubRouting::get(LiveCams::ROUTE_PSEUDOSUB)) {
        $channelId = ubRouting::get(LiveCams::ROUTE_PSEUDOSUB);

        if (!empty($channelId)) {
            $liveCams = new LiveCams();
            $playlistBody = $liveCams->getPseudoSubStream($channelId);
            print($playlistBody);
        }
    }
}
die();
