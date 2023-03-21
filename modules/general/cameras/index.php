<?php

if (cfr('CAMERAS')) {
    $cameras = new Cameras();

    show_window(__('Create new camera'), $cameras->renderCreateForm());
} else {
    show_error(__('Access denied'));
}