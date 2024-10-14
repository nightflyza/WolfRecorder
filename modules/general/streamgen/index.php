<?php

if (cfr('ROOT')) {
    set_time_limit(0);

    $sgTemplate = 'StreamGen';
    $sgLogin = 'admin';
    $sgPassword = 'password';
    $sgNameMask = 'Pseudo Cam';

    $cameras = new Cameras();


    $sgHost = ubRouting::checkPost('sghost') ? ubRouting::post('sghost', 'mres') : '';
    $sgStartPort = ubRouting::checkPost('sgport') ? ubRouting::post('sgport', 'int') : 8554;
    $sgCount = ubRouting::checkPost('sgcount') ? ubRouting::post('sgcount', 'int') : 0;

    $sgModelId = 0;

    function sgGetModelId($sgTemplate = 'StreamGen') {
        $result = 0;
        $models = new Models();
        $allModels = $models->getAllModelData();

        if (!empty($allModels)) {
            foreach ($allModels as $io => $each) {
                if ($each['template'] == $sgTemplate) {
                    $result = $each['id'];
                }
            }
        }
        return ($result);
    }

    function sgForm() {
        $result = '';
        $inputs = '';
        $inputs .= wf_TextInput('sghost', __('Host'), '', false, '15', 'ip');
        $inputs .= wf_TextInput('sgport', __('Start port'), '8554', false, 4, 'digits');
        $inputs .= wf_TextInput('sgcount', __('Count of streams'), ubRouting::post('sgcount'), false, '4', 'digits');
        $inputs .= wf_Submit(__('Generate'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $result .= wf_delimiter();
        $delUrl = '?module=streamgen&flushall=true';
        $result .= wf_ConfirmDialog($delUrl, web_delete_icon() . ' ' . __('Flush all cameras'), __('All streamgen cameras will be destroyed'), 'ubButton', '?module=streamgen', __('Destroy') . '?');
        return ($result);
    }


    if (ubRouting::checkGet('flushall')) {
        $sgModelId = sgGetModelId($sgTemplate);
        if ($sgModelId) {
            $delCount = 0;
            $allCameras = $cameras->getAllCamerasFullData();
            if (!empty($allCameras)) {
                foreach ($allCameras as $io => $each) {
                    $cameraData = $each['CAMERA'];
                    if ($cameraData['modelid'] == $sgModelId) {
                        $cameras->deactivate($cameraData['id']);
                    }
                }

                sleep(5);
                $cameras = new Cameras();
                $allCameras = $cameras->getAllCamerasFullData();
                
                foreach ($allCameras as $io => $each) {
                    $cameraData = $each['CAMERA'];
                    if ($cameraData['modelid'] == $sgModelId) {
                        $delResult = $cameras->delete($cameraData['id']);
                        $delCount++;
                        if (empty($delResult)) {
                            show_warning(__('Camera') . ' ' . $cameraData['comment'] . ' ' . __('Destroyed') . '!');
                        } else {
                            show_error(__('Camera') . ' ' . $cameraData['comment'] . ' ' . __('deletion failed') . '!');
                        }
                    }
                }

                if ($delCount == 0) {
                    show_info(__('No registered pseudo cameras found'));
                }
            } else {
                show_error(__('No registered cameras found'));
            }
        } else {
            show_error(__('Any') . ' ' . $sgTemplate . ' ' . __('models found'));
        }
        show_window('', wf_BackLink('?module=streamgen'));
    } else {
        show_window(__('Generate test cameras'), sgForm());
    }


    if ($sgCount and $sgHost) {
        //gettin modelId
        $sgModelId = sgGetModelId($sgTemplate);

        //creating new one
        if (empty($sgModelId)) {
            $models = new Models();
            $models->create('SG Pseudo Cam', $sgTemplate);
            $sgModelId = sgGetModelId($sgTemplate);
        }

        if ($sgModelId) {
            for ($i = 0; $i < $sgCount; $i++) {
                $nextPort = $sgStartPort + $i;
                $isFree = $cameras->isCameraIpPortFree($sgHost, $nextPort);
                if ($isFree) {
                    $camName = $sgNameMask . ' ' . ($i + 1);
                    $regResult = $cameras->create($sgModelId, $sgHost, $sgLogin, $sgPassword, true, 0, $camName, $nextPort);
                    if (empty($regResult)) {
                        show_success($sgHost . ':' . $nextPort . ' ' . __('Registered'));
                    } else {
                        show_error($sgHost . ':' . $nextPort . ' ' . __('Registration failed'));
                    }
                } else {
                    show_info($sgHost . ':' . $nextPort . ' ' . __('Already registered'));
                }
            }
        } else {
            show_error(__('ModelId not detected'));
        }
    }
} else {
    show_error(__('Access denied'));
}
