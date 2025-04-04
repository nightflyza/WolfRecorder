<?php

if ($system->getAuthEnabled()) {
    if (cfr('ROOT')) {
        class WrSettings extends ConfigForge {
            const URL_ME = '?module=settings';
        }

        $settings = new WrSettings('config/alter.ini', 'config/alter.spec');
        $settings->setFormClass('glamforge');

        $processResult = $settings->process();
        if (!empty($processResult)) {
            show_error($processResult);
        } elseif (ubRouting::checkPost(ConfigForge::FORM_SUBMIT_KEY)) {
            ubRouting::nav($settings::URL_ME);
        }
        show_window(__('System settings'), $settings->renderEditor());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('Authorization engine disabled'));
}
