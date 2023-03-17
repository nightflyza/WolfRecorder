<?php

if (!LOGGED_IN) {
    $loginForm = new YalfLoginForm();
    show_window('', $loginForm->render());
} else {
    //basic logout control
    show_window('', wf_Link('?forceLogout=true', __('Log out')));
}

