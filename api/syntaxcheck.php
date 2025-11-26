<?php

require_once(__DIR__ . '/libs/api.compat.php');
require_once(__DIR__ . '/libs/api.ubrouting.php');

$errorCount = 0;
$baseLibsPath= __DIR__ .'/../';
$mainLibsPath = 'api/libs/';

$failedLibs = array();
$allLibsToCheck = array();


if (ubRouting::optionCliCheck('run', false)) {

    if (ubRouting::optionCliCheck('run', false)) {
        $mainLibsToCheck = rcms_scandir($baseLibsPath . $mainLibsPath, '*.php');
        if (!empty($mainLibsToCheck)) {
            foreach ($mainLibsToCheck as $index=>$eachUbLib) {
                $allLibsToCheck[$mainLibsPath.$eachUbLib] = $baseLibsPath . $mainLibsPath . $eachUbLib;
            }
        }
    }

    
    if (!empty($allLibsToCheck)) {
        foreach ($allLibsToCheck as $index=>$eachUbLib) {
            $lintResult = shell_exec('php -l ' . $eachUbLib.' 2>&1');
            $libLabel=$index;
            if (ispos($lintResult, 'PHP ')) {
                $errorCount++;
                $failedLibs[] = $libLabel;
                print('⚠️ FAILED: ' . $libLabel . PHP_EOL);
                print('🔍 Details:' . PHP_EOL);
                print('=========================' . PHP_EOL);
                print($lintResult.PHP_EOL);
                print('=========================' . PHP_EOL);
            } else {
                print('✅ OK: ' . $libLabel . PHP_EOL);
            }
        }
    } else {
        print('❌ No libs found' . PHP_EOL);
    }

    //summary here
    print('📊 Summary:' . PHP_EOL);
    print('=========================' . PHP_EOL);
    if ($errorCount > 0) {
        print('❌ Found ' . $errorCount . ' issues with libs syntax' . PHP_EOL);
        print('📋 Failed libraries:' . PHP_EOL);
        foreach ($failedLibs as $lib) {
            print('  ⚠️ ' . $lib . PHP_EOL);
        }
    } else {
        print('✨ Everything is Ok' . PHP_EOL);
    }
} else {
    print('ℹ️ Usage: php syntaxcheck.php --[run]' . PHP_EOL);
}
