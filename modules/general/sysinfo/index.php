<?php

if (cfr('SYSINFO')) {


    $sysInfo = new SystemInfo();

    $systemHealth = '';
    if (cfr('ROOT')) {
        $systemHealth .= $sysInfo->renderSerialInfo();
    }
    $systemHealth .= $sysInfo->renderLA();
    $systemHealth .= $sysInfo->renderDisksCapacity();

    show_window(__('System health'), $systemHealth);
    wr_Stats();
} else {
    show_error(__('Access denied'));
}    