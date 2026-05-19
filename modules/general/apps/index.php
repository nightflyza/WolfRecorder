<?php

if (cfr('LIVECAMS')) {
 if ($ubillingConfig->getAlterParam('APPS_ENABLED')) {
    $appsDirectory = new AppsDirectory();
    show_window(__('Apps'), $appsDirectory->renderAppsList());
 } else {
    show_error(__('This module is disabled'));
 }
} else {
    show_error(__('Access denied'));
}