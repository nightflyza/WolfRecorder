<?php

if (cfr('EXPORT')) {
   $export=new Export();
   debarr($export);
} else {
    show_error(__('Access denied'));
}