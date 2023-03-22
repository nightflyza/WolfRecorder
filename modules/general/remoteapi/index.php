<?php

set_time_limit(0);
/**
 * WolfRecorder RemoteAPI implementation
 */
if (ubRouting::checkGet('key')) {
    $key = ubRouting::get('key', 'mres');
    $serial = wr_SerialGet();
    if (!empty($serial)) {
        if ($key == $serial) {
            //key is ok
            if (ubRouting::checkGet('action')) {
                //Loading separate api calls controllers
                $allRemoteApiModules = rcms_scandir(REMOTEAPI_PATH, '*.php');
                if (!empty($allRemoteApiModules)) {
                    foreach ($allRemoteApiModules as $rmodIndex => $eachRModuleController) {
                        $eachRModuleControllerName = basename($eachRModuleController, '.php');
                        require_once (REMOTEAPI_PATH . $eachRModuleController);
                    }
                }
                /*
                 * Exceptions handling
                 */
            } else {
                die('ERROR:GET_NO_ACTION');
            }
        } else {
            die('ERROR:GET_WRONG_KEY');
        }
    } else {
        die('ERROR:NO_UBSERIAL_EXISTS');
    }
} else {
    /*
     * WolfRecorder instance identify handler
     */
    if (ubRouting::checkGet('action')) {
        if (ubRouting::get('action') == 'identify') {
            $serial = wr_SerialGet();
            if (!empty($serial)) {
                $idserial = $serial;
            } else {
                $idserial = wr_SerialInstall();
            }

            //saving serial into temp file required for initial crontab setup
            if (@ubRouting::get('param') == 'save') {
                file_put_contents('exports/wrserial', $idserial);
            }

            //render result
            die(substr($idserial, -4));
        }
    } else {
        die('ERROR:GET_NO_KEY');
    }
}
