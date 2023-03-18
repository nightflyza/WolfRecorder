<?php

if (cfr('ROOT')) {
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

    show_window(__('Create new storage'), $storages->renderCreationForm());
    show_window(__('Available storages'), $storages->renderList());
} else {
    show_error(__('Access denied'));
}