<?php

/**
 * Including all needed APIs and Libs
 */
require_once('api/libs/api.compat.php');
require_once('api/libs/api.yalfcore.php');

$system = new YALFCore();
$yalfLibs = $system->getLibs();

if (!empty($yalfLibs)) {
    foreach ($yalfLibs as $eachLibPath => $eachYalfLayer) {
        require_once($eachLibPath);
    }
}

