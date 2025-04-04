<?php

if ($system->getAuthEnabled()) {
    if (cfr('ROOT')) {

        // Core config routines
        $coreAlter = new CoreForge('config/yalf.ini', 'config/core.spec');
        $coreAlter->setFormClass('glamforge');

        $processCoreResult = $coreAlter->process();
        if (!empty($processCoreResult)) {
            show_error($processCoreResult);
        } elseif (ubRouting::checkPost(ConfigForge::FORM_SUBMIT_KEY)) {
            ubRouting::nav($coreAlter::URL_ME);
        }

        $coreForm = $coreAlter->renderEditor();

        // Alter editor routines
        $settingsAlter = new AlterForge('config/alter.ini', 'config/alter.spec');
        $settingsAlter->setFormClass('glamforge');

        $processAlterResult = $settingsAlter->process();
        if (!empty($processAlterResult)) {
            show_error($processAlterResult);
        } elseif (ubRouting::checkPost(ConfigForge::FORM_SUBMIT_KEY)) {
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
