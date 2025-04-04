<?php

if ($system->getAuthEnabled()) {
    if (cfr('ROOT')) {
        class WrSettings extends ConfigForge {
        }

        $settings = new WrSettings('config/alter.ini', 'config/alter.spec');
        show_window(__('System settings'), $settings->renderEditor());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('Authorization engine disabled'));
}
