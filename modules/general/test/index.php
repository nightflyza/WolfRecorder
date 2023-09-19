<?php

if (cfr('ROOT')) {
    error_reporting(E_ALL);
} else {
    show_error(__('Access denied'));
}