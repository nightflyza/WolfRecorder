<?php

if ($system->getAuthEnabled()) {
    if (cfr('ROOT')) {

        $userManager = new UserManager();

        //User deletion
        if (ubRouting::checkGet($userManager::ROUTE_DELETE)) {
            $userManager->deleteUser(ubRouting::get($userManager::ROUTE_DELETE));
            ubRouting::nav($userManager::URL_ME);
        }

        //User creation
        if (ubRouting::checkPost($userManager::PROUTE_DOREGISTER)) {
            $registerResult = $userManager->createUser();
            if (empty($registerResult)) {
                ubRouting::nav($userManager::URL_ME);
            } else {
                show_error($registerResult);
            }
        }

        //User profile editing
        if (ubRouting::checkPost($userManager::PROUTE_DOEDIT)) {
            $saveResult = $userManager->saveUser();
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

        if (!ubRouting::checkGet($userManager::ROUTE_EDIT) AND ! ubRouting::checkGet($userManager::ROUTE_PERMISSIONS) AND ! ubRouting::checkGet($userManager::ROUTE_NEWUSER)) {
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
                show_window('', wf_BackLink($userManager::URL_ME));
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