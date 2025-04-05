<?php

if ($system->getAuthEnabled()) {
    if (cfr('ROOT')) {

        // Core config routines
        $settingsCore = new CoreForge('config/yalf.ini', 'config/core.spec');
        $settingsCore->setFormClass('glamforge');

        $processCoreResult = $settingsCore->process();

        if (!empty($processCoreResult)) {
            show_error($processCoreResult);
        } elseif (ubRouting::post(ConfigForge::FORM_SUBMIT_KEY) == $settingsCore->getInstanceId()) {
            ubRouting::nav($settingsCore::URL_ME);
        }

        $coreForm = $settingsCore->renderEditor();

        // Alter editor routines
        $settingsAlter = new AlterForge('config/alter.ini', 'config/alter.spec');
        $settingsAlter->setFormClass('glamforge');

        $processAlterResult = $settingsAlter->process();
        if (!empty($processAlterResult)) {
            show_error($processAlterResult);
        } elseif (ubRouting::post(ConfigForge::FORM_SUBMIT_KEY)==$settingsAlter->getInstanceId()) {
            ubRouting::nav($settingsAlter::URL_ME);
        }

        $alterForm = $settingsAlter->renderEditor();

        //forms rendering
        show_window(__('Core'), $coreForm);
        show_window(__('Behavior'), $alterForm);
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('Authorization engine disabled'));
}
