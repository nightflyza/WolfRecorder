<?php

if (cfr('CAMERAS')) {
    $cameras = new Cameras();

    //new camera creation
    if (ubRouting::checkPost(array($cameras::PROUTE_NEWMODEL, $cameras::PROUTE_NEWIP, $cameras::PROUTE_NEWLOGIN, $cameras::PROUTE_NEWPASS, $cameras::PROUTE_NEWSTORAGE))) {
        $creationResult = $cameras->create(ubRouting::post($cameras::PROUTE_NEWMODEL), ubRouting::post($cameras::PROUTE_NEWIP), ubRouting::post($cameras::PROUTE_NEWLOGIN), ubRouting::post($cameras::PROUTE_NEWPASS), ubRouting::post($cameras::PROUTE_NEWACT), ubRouting::post($cameras::PROUTE_NEWSTORAGE), ubRouting::post($cameras::PROUTE_NEWCOMMENT));
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

    //camera deactivation
    if (ubRouting::checkGet($cameras::ROUTE_DEACTIVATE)) {
        $cameras->deactivate(ubRouting::get($cameras::ROUTE_DEACTIVATE));
        ubRouting::nav($cameras::URL_ME . '&' . $cameras::ROUTE_EDIT . '=' . ubRouting::get($cameras::ROUTE_DEACTIVATE));
    }

    //camera activation here
    if (ubRouting::checkGet($cameras::ROUTE_ACTIVATE)) {
        $cameras->activate(ubRouting::get($cameras::ROUTE_ACTIVATE));
        ubRouting::nav($cameras::URL_ME . '&' . $cameras::ROUTE_EDIT . '=' . ubRouting::get($cameras::ROUTE_ACTIVATE));
    }

    if (!ubRouting::checkGet($cameras::ROUTE_EDIT)) {
        show_window(__('Available cameras'), $cameras->renderList());
        $cameraCreationDialog = wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Create new camera'), __('Create new camera'), $cameras->renderCreateForm(), 'ubButton');
        show_window('', $cameraCreationDialog);
        wr_Stats();
    } else {
        //render camera profile
        show_window(__('Edit camera'), $cameras->renderEditForm(ubRouting::get($cameras::ROUTE_EDIT)));
    }
} else {
    show_error(__('Access denied'));
}