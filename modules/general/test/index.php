<?php

if (cfr('TESTING')) {
    show_window('Just a test module', 'subj');
} else {
    show_error(__('Access denied'));
}