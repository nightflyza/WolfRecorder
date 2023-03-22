<?php

/**
 * Returns current instance serial from database
 * 
 * @return string
 */
function wr_SerialGet() {
    $result = '';
    $lairDb = new NyanORM('lair');
    $lairDb->where('key', '=', 'wrid');
    $rawResult = $lairDb->getAll('key');
    if (!empty($rawResult)) {
        $result = $rawResult['wrid']['value'];
    }
    return($result);
}

/**
 * Installs newly generated instance serial into database
 * 
 * @return string
 */
function wr_SerialInstall() {
    $randomid = 'WR' . md5(curdatetime() . zb_rand_string(8));
    $lairDb = new NyanORM('lair');
    $lairDb->data('key', 'wrid');
    $lairDb->data('value', $randomid);
    $lairDb->create();
    return($randomid);
}
