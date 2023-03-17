<?php

if ($system->getAuthEnabled()) {
    if (cfr('ROOT')) {


        $sysConf = new YalfSysConf();
        show_window(__('Edit configs'), $sysConf->renderControls());

        //just phpinfo() callback
        if (ubRouting::checkGet($sysConf::ROUTE_PHPINFO)) {
            phpinfo();
            die();
        }

        //save changes if required
        if (ubRouting::checkPost(array($sysConf::PROUTE_FILEPATH, $sysConf::PROUTE_FILECONTENT))) {
            $saveResult = $sysConf->saveFile();
            if (empty($saveResult)) {
                $fileUrl = base64_encode(ubRouting::post($sysConf::PROUTE_FILEPATH));
                ubRouting::nav($sysConf::URL_ME . '&' . $sysConf::ROUTE_EDIT . '=' . $fileUrl);
            } else {
                show_error($saveResult);
            }
        }
        //render editing interface
        if (ubRouting::checkGet($sysConf::ROUTE_EDIT)) {
            $fileToEdit = base64_decode(ubRouting::get($sysConf::ROUTE_EDIT));
            show_window(__('Edit') . ' ' . $fileToEdit, $sysConf->renderFileEditor());
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('Authorization engine disabled'));
}