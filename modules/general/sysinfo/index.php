<?php

if (cfr('SYSINFO')) {

 	
    $sysInfo = new SystemInfo();

    $systemHealth = $sysInfo->renderLA();
    $systemHealth .= $sysInfo->renderDisksCapacity();

   // $sysInfoZen = new ZenFlow('sysinfoflow', $systemHealth, '3000');
    show_window(__('System health'),  $systemHealth);
} else {
    show_error(__('Access denied'));
}    