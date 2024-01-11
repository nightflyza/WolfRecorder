<?php

if (cfr('CAMERAS')) {
    $cameras = new Cameras();

    //new camera creation
    if (ubRouting::checkPost(array($cameras::PROUTE_NEWMODEL, $cameras::PROUTE_NEWIP, $cameras::PROUTE_NEWLOGIN, $cameras::PROUTE_NEWPASS))) {
        $newModelId = ubRouting::post($cameras::PROUTE_NEWMODEL);
        $newIp = ubRouting::post($cameras::PROUTE_NEWIP);
        $newLogin = ubRouting::post($cameras::PROUTE_NEWLOGIN);
        $newPass = ubRouting::post($cameras::PROUTE_NEWPASS);
        $newAct = ubRouting::post($cameras::PROUTE_NEWACT);
        $newStorageId = ubRouting::post($cameras::PROUTE_NEWSTORAGE);
        $newComment = ubRouting::post($cameras::PROUTE_NEWCOMMENT);

        $creationResult = $cameras->create($newModelId, $newIp, $newLogin, $newPass, $newAct, $newStorageId, $newComment);
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

    //camera comment editing
    if (ubRouting::checkPost($cameras::PROUTE_ED_CAMERAID_ACT)) {
        $renameResult = $cameras->saveComment(ubRouting::post($cameras::PROUTE_ED_CAMERAID_ACT), ubRouting::post($cameras::PROUTE_ED_COMMENT_ACT));
        if ($renameResult) {
            show_error($renameResult);
        } else {
            ubRouting::nav($cameras::URL_ME . '&' . $cameras::ROUTE_EDIT . '=' . ubRouting::post($cameras::PROUTE_ED_CAMERAID_ACT));
        }
    }

    //camera editing
    if (ubRouting::checkPost(array($cameras::PROUTE_ED_CAMERAID, $cameras::PROUTE_ED_MODEL, $cameras::PROUTE_ED_IP, $cameras::PROUTE_ED_LOGIN, $cameras::PROUTE_ED_PASS, $cameras::PROUTE_ED_STORAGE))) {
        $edCameraId = ubRouting::post($cameras::PROUTE_ED_CAMERAID);
        $edModelId = ubRouting::post($cameras::PROUTE_ED_MODEL);
        $edIp = ubRouting::post($cameras::PROUTE_ED_IP);
        $edLogin = ubRouting::post($cameras::PROUTE_ED_LOGIN);
        $edPass = ubRouting::post($cameras::PROUTE_ED_PASS);
        $edStorageId = ubRouting::post($cameras::PROUTE_ED_STORAGE);
        $edComment = ubRouting::post($cameras::PROUTE_ED_COMMENT);
        $editingResult = $cameras->save($edCameraId, $edModelId, $edIp, $edLogin, $edPass, $edStorageId, $edComment);
        if ($editingResult) {
            show_error($editingResult);
        } else {
            ubRouting::nav($cameras::URL_ME . '&' . $cameras::ROUTE_EDIT . '=' . $edCameraId);
        }
    }

    //camera archive ajax stats
    if (ubRouting::checkGet($cameras::ROUTE_AJ_ARCHSTATS)) {
        die($cameras->renderCameraArchiveStats(ubRouting::get($cameras::ROUTE_AJ_ARCHSTATS)));
    }


    if (!ubRouting::checkGet($cameras::ROUTE_EDIT)) {
        //just listing available cameras list
        show_window(__('Available cameras'), $cameras->renderList());
        $cameraCreationDialog = wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Create new camera'), __('Create new camera'), $cameras->renderCreateForm(), 'ubButton');
        show_window('', $cameraCreationDialog);
        wr_Stats();
    } else {
        //render camera profile
        show_window(__('Edit camera'), $cameras->renderCameraProfile(ubRouting::get($cameras::ROUTE_EDIT)));
    }
} else {
    show_error(__('Access denied'));
}
