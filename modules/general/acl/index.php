<?php

if (cfr('ACL')) {
    $acl = new ACL(true);
    if (ubRouting::checkPost(array($acl::PROUTE_NEWLOGIN, $acl::PROUTE_NEWCAMID))) {
        $creationResult = $acl->create(ubRouting::post($acl::PROUTE_NEWLOGIN), ubRouting::post($acl::PROUTE_NEWCAMID));
        if ($creationResult) {
            show_error($creationResult);
        } else {
            ubRouting::nav($acl::URL_ME);
        }
    }
    show_window(__('Create new rule'), $acl->renderCreateForm());
    show_window(__('Cameras access') . ' (' . __('ACL') . ')', $acl->renderAclList());
} else {
    show_error(__('Access denied'));
}
