<?php

if (ubRouting::get('live')) {
    $channelId = ubRouting::get(LiveCams::ROUTE_PSEUDOLIVE);

    if (!empty($channelId)) {
        $liveCams = new LiveCams();
        $playlistBody = $liveCams->getPseudoStream($channelId);
        print($playlistBody);
    }
}
die();
