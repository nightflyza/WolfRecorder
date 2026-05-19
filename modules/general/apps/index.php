<?php

if (cfr('LIVECAMS')) {
 if ($ubillingConfig->getAlterParam('APPS_ENABLED')) {
    show_window(__('Apps'), 'TODO');
 } else {
    show_error(__('Apps are not enabled'));
 }
} else {
    show_error(__('Access denied'));
}