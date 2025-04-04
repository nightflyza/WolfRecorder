<?php

if (cfr('ROOT')) {
if ($ubillingConfig->getAlterParam('LICENSES_ENABLED')) {
    //key deletion
    if (ubRouting::checkGet('licensedelete')) {
        $avarice = new Avarice();
        $avarice->deleteKey(ubRouting::get('licensedelete'));
        ubRouting::nav('?module=licensekeys');
    }

    //key installation
    if (ubRouting::checkPost('createlicense')) {
        $avarice = new Avarice();
        if ($avarice->createKey(ubRouting::post('createlicense'))) {
            ubRouting::nav('?module=licensekeys');
        } else {
            show_error(__('Unacceptable license key'));
        }
    }
    //key editing
    if (ubRouting::checkPost(array('editlicense', 'editdbkey'))) {
        $avarice = new Avarice();
        if ($avarice->updateKey(ubRouting::post('editdbkey'), ubRouting::post('editlicense'))) {
            ubRouting::nav('?module=licensekeys');
        } else {
            show_error(__('Unacceptable license key'));
        }
    }

    //displaying serial for license offering
    $hostid = wr_SerialGet();
    if (!empty($hostid)) {
        //render current Ubilling serial info
        show_info(__('Use this WolfRecorder serial for license keys purchase') . ': ' . wf_tag('b') . $hostid . wf_tag('b', true));
        //show available license keys
        wr_LicenseLister();
    }

    wr_Stats();
} else {
    show_error(__('This module is disabled'));
}
} else {
    show_error(__('Access denied'));
}



