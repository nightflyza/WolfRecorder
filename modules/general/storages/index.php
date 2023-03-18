<?php

if (cfr('ROOT')) {
    $storages=new Storages();
    
} else {
    show_error(__('Access denied'));
}