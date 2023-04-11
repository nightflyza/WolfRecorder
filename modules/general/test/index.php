<?php

if (cfr('ROOT')) {
    error_reporting(E_ALL);
    set_time_limit(0);
 
//    $baseTs=time();
//    for ($i=0;$i<20000;$i++) {
//        $newTs=$baseTs-($i*60);
//        $newDate=date("Y-m-d_H-i-s",$newTs);
//        $newName=$newDate.'.mp4';
//        file_put_contents('/wrstorage/ufdlou07km7/'.$newName, zb_rand_string(20));
//    }
 
} else {
    show_error(__('Access denied'));
}