<?php

require_once ('libs/api.compat.php');
require_once ('libs/api.ubrouting.php');


if (ubRouting::optionCliCheck('run', false)) {

    $diffDumpFlag = ubRouting::optionCliCheck('dumpdiff', false);
    $errorCount=0;

    $ubillingLibsPath = __DIR__ . '/../../ubilling/api/libs/';
    $yalfLibsPath = __DIR__ . '/libs/';

    $ignoreList = array('api.compat.php', 'api.ubconfig.php', 'api.mysql.php'); //that libs may be different
    $ignoreList = array_flip($ignoreList);

    $allYalfLibs = rcms_scandir($yalfLibsPath, '*.php');
    $allUbillingLibs = rcms_scandir($ubillingLibsPath, '*.php');
    if (!empty($allYalfLibs)) {
        if (!empty($allUbillingLibs)) {
            $allYalfLibs = array_flip($allYalfLibs);
            $allUbillingLibs = array_flip($allUbillingLibs);
            foreach ($allYalfLibs as $eachYalfLib => $index) {
                if (file_exists($ubillingLibsPath . $eachYalfLib)) {
                    if (!isset($ignoreList[$eachYalfLib])) {
                        $diffResult = shell_exec('diff --ignore-all-space ' . $yalfLibsPath . $eachYalfLib . ' ' . $ubillingLibsPath . $eachYalfLib);
                        if (!empty($diffResult)) {
                        	$errorCount++;
                            print('FAILED: ' . $eachYalfLib . PHP_EOL);
                            if ($diffDumpFlag) {
                                print('=========================' . PHP_EOL);
                                print_r($diffResult);
                                print('=========================' . PHP_EOL);
                            }
                        } else {
                            print('OK: ' . $eachYalfLib . PHP_EOL);
                        }
                    }
                }
            }

            //summary here
            print('=========================' . PHP_EOL);
            if ($errorCount>0) {
            	print('Found '.$errorCount.' issues with libs freshness'.PHP_EOL);
            } else {
            	print('Everything is Ok'.PHP_EOL);
            }
        } else {
            print('Error: no Ubilling libs at specified path not found: ' . $ubillingLibsPath . PHP_EOL);
        }
    } else {
        print('Error: no YALF libs at specified path not found: ' . $yalfLibsPath . PHP_EOL);
    }
} else {
    print('Usage: php ./api/checkfreshness.php --run [--dumpdiff]' . PHP_EOL);
}
