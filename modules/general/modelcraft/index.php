<?php
if (cfr('MODELS')) {
    $modelCraft = new ModelCraft();

    //module controls here
    show_window('', $modelCraft->renderControls());

    if (ubRouting::checkGet($modelCraft::ROUTE_EXPLORER)) {
        //onvif-explorer interface
        show_window(__('ONVIF device explorer'), $modelCraft->renderExploreForm());
    }


    //performing device exploration on request
    $exploreRequest = array(
        $modelCraft::PROUTE_EXPLORE,
        $modelCraft::PROUTE_EXPLORE_IP,
        $modelCraft::PROUTE_EXPLORE_LOGIN,
        $modelCraft::PROUTE_EXPLORE_PASSWORD
    );
    if (ubRouting::checkPost($exploreRequest)) {
        if (zb_PingICMP(ubRouting::post($modelCraft::PROUTE_EXPLORE_IP))) {
            $pollingResult = $modelCraft->pollDevice(
                ubRouting::post($modelCraft::PROUTE_EXPLORE_IP),
                ubRouting::post($modelCraft::PROUTE_EXPLORE_LOGIN),
                ubRouting::post($modelCraft::PROUTE_EXPLORE_PASSWORD)
            );
            if (!empty($pollingResult)) {
                show_window(__('Device data'), $modelCraft->renderPollingResults($pollingResult));
            } else {
                show_error(__('Strange exception') . ': ' . __('Something went wrong') . '. ' . __('Device polling data is empty') . '.');
            }
        } else {
            show_error(__('The device is currently unavailable'));
        }
    } else {
        if (!ubRouting::checkGet($modelCraft::ROUTE_EXPLORER)) {        //rendering custom templates list here
            show_window(__('Custom device templates'), $modelCraft->renderCustomTemplatesList());
        }
    }

    //new custom device template creation
    $templateRequest = array(
        $modelCraft::PROUTE_TPLCREATE_DEV,
        $modelCraft::PROUTE_TPLCREATE_MAIN,
        $modelCraft::PROUTE_TPLCREATE_SUB,
        $modelCraft::PROUTE_TPLCREATE_PROTO,
        $modelCraft::PROUTE_TPLCREATE_PORT,
    );

    if (ubRouting::checkPost($templateRequest)) {
        $modelCraft->createCustomTemplate(
            ubRouting::post($modelCraft::PROUTE_TPLCREATE_DEV),
            ubRouting::post($modelCraft::PROUTE_TPLCREATE_PROTO),
            ubRouting::post($modelCraft::PROUTE_TPLCREATE_MAIN),
            ubRouting::post($modelCraft::PROUTE_TPLCREATE_SUB),
            ubRouting::post($modelCraft::PROUTE_TPLCREATE_PORT),
            ubRouting::post($modelCraft::PROUTE_TPLCREATE_SOUND),
            ubRouting::post($modelCraft::PROUTE_TPLCREATE_PTZ)
        );
        ubRouting::nav($modelCraft::URL_ME);
    }

    //existing template deletion
    if (ubRouting::checkGet($modelCraft::ROUTE_TPL_DEL)) {
        $deletionResult = $modelCraft->deleteCustomTemplate(ubRouting::get($modelCraft::ROUTE_TPL_DEL));
        if (empty($deletionResult)) {
            ubRouting::nav($modelCraft::URL_ME);
        } else {
            show_error($deletionResult);
        }
    }
} else {
    show_error(__('Access denied'));
}
