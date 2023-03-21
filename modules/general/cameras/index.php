<?php

if (cfr('CAMERAS')) {
    $cameras = new Cameras();

    //new camera creation
    if (ubRouting::checkPost(array($cameras::PROUTE_NEWMODEL, $cameras::PROUTE_NEWIP, $cameras::PROUTE_NEWLOGIN, $cameras::PROUTE_NEWPASS, $cameras::PROUTE_NEWSTORAGE))) {
        $creationResult = $cameras->create(ubRouting::post($cameras::PROUTE_NEWMODEL), ubRouting::post($cameras::PROUTE_NEWIP), ubRouting::post($cameras::PROUTE_NEWLOGIN), ubRouting::post($cameras::PROUTE_NEWPASS), ubRouting::post($cameras::PROUTE_NEWACT), ubRouting::post($cameras::PROUTE_NEWSTORAGE));
        if ($creationResult) {
            show_error($creationResult);
        } else {
            ubRouting::nav($cameras::URL_ME);
        }
    }

    //camera deletion
    if (ubRouting::checkGet($cameras::ROUTE_DEL)) {
        $deletionResult = $cameras->delete(ubRouting::get($cameras::ROUTE_DEL));
        if ($deletionResult) {
            show_error($deletionResult);
        } else {
            ubRouting::nav($cameras::URL_ME);
        }
    }

    show_window(__('Create new camera'), $cameras->renderCreateForm());
    show_window(__('Available cameras'), $cameras->renderList());
} else {
    show_error(__('Access denied'));
}