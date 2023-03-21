<?php

if (cfr('MODELS')) {
    $models = new Models();

    //model creation
    if (ubRouting::checkPost(array($models::PROUTE_NEWMODELNAME, $models::PROUTE_NEWMODELTPL))) {
        $creationResult = $models->create(ubRouting::post($models::PROUTE_NEWMODELNAME), ubRouting::post($models::PROUTE_NEWMODELTPL));
        if ($creationResult) {
            show_error($creationResult);
        } else {
            ubRouting::nav($models::URL_ME);
        }
    }

    //model deletion
    if (ubRouting::checkGet($models::ROUTE_DELMODEL)) {
        $deletionResult = $models->delete(ubRouting::get($models::ROUTE_DELMODEL));
        if ($deletionResult) {
            show_error($deletionResult);
        } else {
            ubRouting::nav($models::URL_ME);
        }
    }

    show_window(__('Create new model'), $models->renderCreationForm());
    show_window(__('Available models'), $models->renderList());
} else {
    show_error(__('Access denied'));
}