<?php

/**
 * Including all needed APIs and Libs
 */
require_once('api/libs/api.compat.php');
require_once('api/libs/api.yalfcore.php');

//preventing loading of icecream on PHP < 5.6
if (PHP_VERSION_ID >= 50638) {
    require_once('api/libs/api.ic.php');
}

$system = new YALFCore();
$yalfLibs = $system->getLibs();

if (!empty($yalfLibs)) {
    foreach ($yalfLibs as $eachLibPath => $eachYalfLayer) {
        require_once($eachLibPath);
    }
}

