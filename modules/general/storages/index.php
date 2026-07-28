<?php

if (cfr('STORAGES')) {
    $storages = new Storages();

    //storage creation
    if (ubRouting::checkPost(array($storages::PROUTE_PATH, $storages::PROUTE_NAME))) {
        $creationResult = $storages->create(ubRouting::post($storages::PROUTE_PATH), ubRouting::post($storages::PROUTE_NAME));
        if ($creationResult) {
            show_error($creationResult);
        } else {
            ubRouting::nav($storages::URL_ME);
        }
    }

    //storage deletion
    if (ubRouting::checkGet($storages::ROUTE_DEL)) {
        $deletionResult = $storages->delete(ubRouting::get($storages::ROUTE_DEL));
        if ($deletionResult) {
            show_error($deletionResult);
        } else {
            ubRouting::nav($storages::URL_ME);
        }
    }

    //storage edit
    if (ubRouting::checkPost(array($storages::PROUTE_ED_STORAGE, $storages::PROUTE_ED_NAME))) {
        $storages->saveName(ubRouting::post($storages::PROUTE_ED_STORAGE), ubRouting::post($storages::PROUTE_ED_NAME));
        ubRouting::nav($storages::URL_ME);
    }

    //optional storages IO load view
    if (ubRouting::checkGet($storages::ROUTE_IOLOAD)) {
        $ioLoadZen = new ZenFlow('storio', $storages->renderIoLoad(), 3000);
        show_window(__('Storages load'), $ioLoadZen->render());
        show_window('', wf_BackLink($storages::URL_ME));
    } else {
        show_window(__('Create new storage'), $storages->renderCreationForm());
        show_window(__('Available storages'), $storages->renderList());
        show_window(__('Storage of user exported videos'), $storages->renderRecDlStorage());
        show_window('', wf_Link($storages::URL_ME . '&' . $storages::ROUTE_IOLOAD . '=true', wf_img('skins/icon_disks.png') . ' ' . __('Storages load'), false, 'ubButton'));
    }
} else {
    show_error(__('Access denied'));
}
