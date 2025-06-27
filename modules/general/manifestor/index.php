<?php

$altCfg = $ubillingConfig->getAlter();

$customAppIcons = array();
$appName = $system->getPageTitle();
$shortName = $appName;
$displayMode = 'standalone';

if (isset($altCfg['WA_NAME'])) {
    if (!empty($altCfg['WA_NAME'])) {
        $appName = $altCfg['WA_NAME'];
        $shortName = $altCfg['WA_NAME'];
    }
}

if (isset($altCfg['WA_ICON_192']) and isset($altCfg['WA_ICON_512'])) {
    if (!empty($altCfg['WA_ICON_192']) and !empty($altCfg['WA_ICON_512'])) {
        $customAppIcons = array(
            0 => array('src' => $altCfg['WA_ICON_192'], 'sizes' => '192x192', 'type' => 'image/png'),
            1 => array('src' => $altCfg['WA_ICON_512'], 'sizes' => '512x512', 'type' => 'image/png')
        );
    }
}

if (isset($altCfg['WA_DISPLAY'])) {
    if (!empty($altCfg['WA_DISPLAY'])) {
        $displayMode = $altCfg['WA_DISPLAY'];
    }
}

$manifestor = new Manifestator();
if ($customAppIcons) {
    $manifestor->setIcons($customAppIcons);
}

$manifestor->setDisplay($displayMode);
$manifestor->setName($appName);
$manifestor->setShortName($appName);
$manifestor->render();
