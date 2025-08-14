<?php

if (cfr('ROOT')) {

    set_time_limit(0);
    $updateManager = new UpdateManager();
    $messages = new UbillingMessageHelper();


    if (ubRouting::checkGet('checkupdates')) {
        $localSystemVersion = wr_getLocalSystemVersion();
        $latestRelease = wr_GetReleaseInfo('STABLE');
        $latestNightlyBuild = wr_GetReleaseInfo('CURRENT');
        $latestReleaseLabel = wr_RenderUpdateInfo($latestRelease, 'STABLE');
        $latestNightlyBuildLabel = wr_RenderUpdateInfo($latestNightlyBuild, 'CURRENT');
        
        $stableUpgradable = false;
        $nightlyUpgradable = false;
        
        if ($latestRelease !== false and $localSystemVersion != $latestRelease) {
            $stableUpgradable = true;
        }
        
        if ($latestNightlyBuild !== false and $localSystemVersion != $latestNightlyBuild) {
            $nightlyUpgradable = true;
        }
        
        $styleStable = ($localSystemVersion == $latestRelease and $latestRelease !== false) ? 'success' : 'warning';
        $styleNightly = ($localSystemVersion == $latestNightlyBuild and $latestNightlyBuild !== false) ? 'success' : 'warning';
        
        $remoteReleasesInfo = $messages->getStyledMessage($latestReleaseLabel, $styleStable);
        $remoteReleasesInfo .= $messages->getStyledMessage($latestNightlyBuildLabel, $styleNightly);
        //upgrade controls here
        $upgradeControls = '';
        if ($stableUpgradable) {
            $upgradeControls .= wf_Link($updateManager::URL_ME . '&' . $updateManager::ROUTE_AUTOSYSUPGRADE . '=STABLE', wf_img('skins/icon_ok.gif') . ' ' . __('Upgrade to stable release'), false, 'ubButton') . ' ';
        }
        if ($nightlyUpgradable) {
            $upgradeControls .= wf_Link($updateManager::URL_ME . '&' . $updateManager::ROUTE_AUTOSYSUPGRADE . '=CURRENT', wf_img('skins/icon_cache.png') . ' ' . __('Upgrade to nightly build'), false, 'ubButton') . ' ';
        }
        $remoteReleasesInfo .= wf_delimiter(0);
        $remoteReleasesInfo .= $upgradeControls;


        die($remoteReleasesInfo);
    }



    //automatic upgrade
    if (ubRouting::checkGet($updateManager::ROUTE_AUTOSYSUPGRADE)) {
        $updateBranch = ubRouting::get($updateManager::ROUTE_AUTOSYSUPGRADE);
        $currentSystemVersion = wr_getLocalSystemVersion();
        $updateBranchVersion = wr_GetReleaseInfo($updateBranch);
        if ($currentSystemVersion and $updateBranchVersion) {
            if ($currentSystemVersion != $updateBranchVersion) {
                //running upgrade process
                if (ubRouting::checkPost($updateManager::PROUTE_UPGRADEAGREE)) {
                    $upgradeResult = $updateManager->performAutoUpgrade($updateBranch);
                    if ($upgradeResult) {
                        show_error($upgradeResult);
                    } else {
                        ubRouting::nav($updateManager::URL_ME);
                    }
                } else {
                    //confirmation form
                    $confirmationLabel = __('This will update your WolfRecorder') . ' ' . __('from') . ' ' . $currentSystemVersion . ' ' . __('to') . ' ' . $updateBranchVersion;
                    $confirmationLabel .= wf_delimiter(0);

                    $confirmationInputs = $confirmationLabel;
                    $confirmationInputs .= wf_CheckInput($updateManager::PROUTE_UPGRADEAGREE, __('I`m ready'), true, false);
                    $confirmationInputs .= wf_Submit(__('System update'));
                    $confirmationForm = wf_Form('', 'POST', $confirmationInputs, 'glamour');
                    $confirmationForm .= wf_FormDisabler();
                    show_window(__('System update'), $confirmationForm);
                }
            } else {
                show_info(__('Current system version') . ': ' . $currentSystemVersion);
                if ($updateBranch == 'STABLE') {
                    show_info(__('Latest stable WolfRecorder release is') . ': ' . $currentSystemVersion);
                }
                if ($updateBranch == 'CURRENT') {
                    show_info(__('Latest nightly WolfRecorder build is') . ': ' . $updateBranchVersion);
                }
                show_success(__('Your software version is already up to date'));
            }
        } else {
            show_error(__('Something went wrong'));
        }
        show_window('', wf_BackLink($updateManager::URL_ME));
    } else {
        if (!ubRouting::checkGet('applysql') and ! ubRouting::checkGet('showconfigs')) {
            //updates check
            show_window('', $updateManager->renderVersionInfo());

            //available updates lists render
            show_window(__('Database schema updates'), $updateManager->renderSqlDumpsList());
            show_window(__('Configuration files updates'), $updateManager->renderConfigsList());
        } else {
            //mysql dumps applying interface
            if (ubRouting::checkGet('applysql')) {
                show_window(__('Database schema updates'), $updateManager->applyMysqlDump(ubRouting::get('applysql')));
            }

            if (ubRouting::checkGet('showconfigs')) {
                show_window(__('Configuration files updates'), $updateManager->applyConfigOptions(ubRouting::get('showconfigs')));
            }
        }
    }
} else {
    show_error(__('Access denied'));
}
