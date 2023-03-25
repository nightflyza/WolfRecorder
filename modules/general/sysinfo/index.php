<?php

if (cfr('SYSINFO')) {

    $sysInfo = new SystemInfo();
    $systemHealth = $sysInfo->renderLA();
    $systemHealth .= $sysInfo->renderDisksCapacity();
    show_window(__('System health'), $systemHealth);
} else {
    show_error(__('Access denied'));
}    