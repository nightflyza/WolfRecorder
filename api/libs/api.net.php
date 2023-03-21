<?php

/**
 * Checks have some IP valid format or not?
 *
 * @param string $ip
 *
 * @return bool
 */
function zb_isIPValid($ip) {
    $result = false;
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $result = true;
    }
    return($result);
}

/**
 * Returns result of fast icmp ping
 * 
 * @param string $ip devide IP to ping
 * 
 * @return bool
 */
function zb_PingICMP($ip) {
    $globconf = parse_ini_file(CONFIG_PATH . "binpaths.ini");
    $ping = $globconf['PING'];
    $sudo = $globconf['SUDO'];
    $ping_command = $sudo . ' ' . $ping . ' -i 0.01 -c 1 ' . $ip;
    $ping_result = shell_exec($ping_command);
    if (strpos($ping_result, 'ttl')) {
        return (true);
    } else {
        return(false);
    }
}
