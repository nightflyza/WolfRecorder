<?php

require_once('libs/api.compat.php');
require_once('libs/api.ubrouting.php');


if (ubRouting::optionCliCheck('run', false)) {
    $errorCount = 0;
    $yalfLibsPath = __DIR__ . '/libs/';

    $allYalfLibs = rcms_scandir($yalfLibsPath, '*.php');
    if (!empty($allYalfLibs)) {
        $allYalfLibs = array_flip($allYalfLibs);
        foreach ($allYalfLibs as $eachYalfLib => $index) {
            $lintResult = shell_exec('php -l ' . $yalfLibsPath . $eachYalfLib.' 2>&1');
            if (ispos($lintResult, 'PHP ')) {
                $errorCount++;
                print('FAILED: ' . $eachYalfLib . PHP_EOL);
                print('=========================' . PHP_EOL);
                print($lintResult.PHP_EOL);
                print('=========================' . PHP_EOL);
            } else {
                print('OK: ' . $eachYalfLib . PHP_EOL);
            }
        }
    }

    //summary here
    print('=========================' . PHP_EOL);
    if ($errorCount > 0) {
        print('Found ' . $errorCount . ' issues with libs syntax' . PHP_EOL);
    } else {
        print('Everything is Ok' . PHP_EOL);
    }
} else {
    print('Usage: php ./api/syntaxcheck.php --run' . PHP_EOL);
}
