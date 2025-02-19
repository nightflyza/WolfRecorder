<?php

if ($system->getAuthEnabled()) {
    if (cfr('ROOT')) {

        $userManager = new UserManager();

        //ghostmode init
        if (ubRouting::checkGet($userManager::ROUTE_GHOSTMODE)) {
            if (cfr('ROOT')) {
                $system->initGhostMode(ubRouting::get($userManager::ROUTE_GHOSTMODE));
                ubRouting::nav('index.php');
            } else {
                show_error(__('Access denied'));
            }
        }


        //User deletion
        if (ubRouting::checkGet($userManager::ROUTE_DELETE)) {
            $userManager->deleteUser(ubRouting::get($userManager::ROUTE_DELETE));
            ubRouting::nav($userManager::URL_ME);
        }

        //User creation
        if (ubRouting::checkPost($userManager::PROUTE_DOREGISTER)) {
            //all of this props are required for normal registration
            $requiredParams = array(
                $userManager::PROUTE_USERNAME,
                $userManager::PROUTE_PASSWORD,
                $userManager::PROUTE_PASSWORDCONFIRM,
                $userManager::PROUTE_USERROLE,
            );

            if (ubRouting::checkPost($requiredParams)) {
                $registerResult = $userManager->createUser(ubRouting::post($userManager::PROUTE_USERNAME), ubRouting::post($userManager::PROUTE_PASSWORD), ubRouting::post($userManager::PROUTE_PASSWORDCONFIRM), ubRouting::post($userManager::PROUTE_USERROLE));
                if (empty($registerResult)) {
                    ubRouting::nav($userManager::URL_ME);
                } else {
                    show_error($registerResult);
                }
            }
        }

        //User profile editing
        if (ubRouting::checkPost($userManager::PROUTE_DOEDIT)) {
            $saveResult = $userManager->saveUser(ubRouting::post($userManager::PROUTE_DOEDIT), ubRouting::post($userManager::PROUTE_PASSWORD), ubRouting::post($userManager::PROUTE_PASSWORDCONFIRM));
            if (empty($saveResult)) {
                ubRouting::nav($userManager::URL_ME . '&' . $userManager::ROUTE_EDIT . '=' . ubRouting::post($userManager::PROUTE_DOEDIT));
            } else {
                show_error($saveResult);
            }
        }

        //User permissions/rights editing
        if (ubRouting::checkPost($userManager::PROUTE_DOPERMS)) {
            $permEditResult = $userManager->savePermissions();
            if (empty($permEditResult)) {
                ubRouting::nav($userManager::URL_ME . '&' . $userManager::ROUTE_PERMISSIONS . '=' . ubRouting::post($userManager::PROUTE_DOPERMS));
            } else {
                show_error($permEditResult);
            }
        }

        if (!ubRouting::checkGet($userManager::ROUTE_EDIT) and ! ubRouting::checkGet($userManager::ROUTE_PERMISSIONS) and ! ubRouting::checkGet($userManager::ROUTE_NEWUSER)) {
            //rendering existing users list
            show_window(__('Available users'), $userManager->renderUsersList());
        } else {
            //rendering user data edit interface
            if (ubRouting::checkGet($userManager::ROUTE_EDIT)) {
                show_window(__('Edit user') . ' ' . ubRouting::get($userManager::ROUTE_EDIT), $userManager->renderEditForm(ubRouting::get($userManager::ROUTE_EDIT)));
                show_window('', wf_BackLink($userManager::URL_ME));
            }


            //rendering user permissions edit interface
            if (ubRouting::checkGet($userManager::ROUTE_PERMISSIONS)) {
                show_window(__('Edit user permissions') . ' ' . ubRouting::get($userManager::ROUTE_PERMISSIONS), $userManager->renderPermissionsForm(ubRouting::get($userManager::ROUTE_PERMISSIONS)));
                $permControls = wf_BackLink($userManager::URL_ME);
                if (cfr('ROOT')) {
                    $myLogin = whoami();
                    $userLogin = ubRouting::get($userManager::ROUTE_PERMISSIONS);
                    if ($userLogin != $myLogin) {
                        $ghostModeLabel = __('Login as') . ' ' . $userLogin . ' ' . __('in ghost mode');
                        $permControls .= ' ' . wf_Link($userManager::URL_ME . '&' . $userManager::ROUTE_GHOSTMODE . '=' . $userLogin, wf_img('skins/ghost.png') . ' ' . $ghostModeLabel, false, ' ubButton');
                    }
                }
                show_window('', $permControls);
            }

            //rendering new user creation form
            if (ubRouting::checkGet($userManager::ROUTE_NEWUSER)) {
                show_window(__('Register new user'), $userManager->renderRegisterForm());
                show_window('', wf_BackLink($userManager::URL_ME));
            }
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('Authorization engine disabled'));
}
