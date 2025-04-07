<?php


/**
 * Renders the UX loading spinner
 *
 * @return string
 */
function wr_YalfLoaderRender() {
    global $ubillingConfig;

    $result = '';

    if ($ubillingConfig->getAlterParam('PAGE_LOAD_INDICATOR')) {
        $uiLoader = 'modules/jsc/yalfloader.html';
        if (file_exists($uiLoader)) {
            $result = file_get_contents($uiLoader);
        }
    }
    return ($result);
}
