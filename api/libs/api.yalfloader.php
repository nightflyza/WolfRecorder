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
        $result = file_get_contents('modules/jsc/yalfloader.html');
    }
    return ($result);
}
