<?php

if ($system->getAuthEnabled()) {
    if (cfr('ROOT')) {

        // Core config routines
        $settingsCore = new CoreForge('config/yalf.ini', 'config/core.spec');
        $settingsCore->setFormClass('glamforge');

        $processCoreResult = $settingsCore->process();

        if (!empty($processCoreResult)) {
            show_error($processCoreResult);
            log_register('SETTINGS CORE SAVE FAILED');
        } elseif (ubRouting::post(ConfigForge::FORM_SUBMIT_KEY) == $settingsCore->getInstanceId()) {
            log_register('SETTINGS CORE SAVED');
            ubRouting::nav($settingsCore::URL_ME);
        }

        $coreForm = $settingsCore->renderEditor();

        // Alter editor routines
        $settingsAlter = new AlterForge('config/alter.ini', 'config/alter.spec');
        $settingsAlter->setFormClass('glamforge');

        $processAlterResult = $settingsAlter->process();
        if (!empty($processAlterResult)) {
            show_error($processAlterResult);
            log_register('SETTINGS ALTER SAVE FAILED');
        } elseif (ubRouting::post(ConfigForge::FORM_SUBMIT_KEY)==$settingsAlter->getInstanceId()) {
            log_register('SETTINGS ALTER SAVED');
            ubRouting::nav($settingsAlter::URL_ME);
        }

        $alterForm = $settingsAlter->renderEditor();

        //forms rendering
        show_window(__('Core'), $coreForm);
        show_window(__('Behavior'), $alterForm);
        wr_Stats();
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('Authorization engine disabled'));
}
