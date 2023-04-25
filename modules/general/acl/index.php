<?php

if (cfr('ACL')) {
    $acl = new ACL(true);
    show_window(__('Cameras access') . ' (' . __('ACL') . ')', $acl->renderAclList());
} else {
    show_error(__('Access denied'));
}
