<?php

error_reporting(E_ALL);

/**
 * is Xhprof Hierarchical Profiler enabled?
 */
$minimalConfig = @parse_ini_file('config/yalf.ini');
if (@$minimalConfig['XHPROF']) {
    define('XHPROF', 1);
} else {
    define('XHPROF', 0);
}


/**
 * rcms-like commons consts defines
 */
define('CONFIG_PATH', 'config/');
define('DATA_PATH', 'content/');
define('USERS_PATH', 'content/users/');
define('MODULES_PATH', 'modules/general/');
define('REMOTEAPI_PATH', 'modules/remoteapi/');

/**
 * Profiler init
 */
if (XHPROF) {
    $yalfConf = parse_ini_file(CONFIG_PATH . 'yalf.ini');
    if ($yalfConf['XHPROF_PATH']) {
        $xhProfLibsPath = $yalfConf['XHPROF_PATH'];
    } else {
        $xhProfLibsPath = 'xhprof';
    }
    define("XHPROF_ROOT", __DIR__ . '/' . $xhProfLibsPath);
    require_once(XHPROF_ROOT . '/xhprof_lib/utils/xhprof_lib.php');
    require_once(XHPROF_ROOT . '/xhprof_lib/utils/xhprof_runs.php');
    //append XHPROF_FLAGS_NO_BUILTINS if your PHP instance crashes
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}

/**
 * Default headers
 */
header('Last-Modified: ' . gmdate('r'));
header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

/**
 * Page generation time counters begins
 */
$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];
$query_counter = 0;


/**
 * System initialization
 */
require_once('api/autoloader.php'); //preloading required libs
define('LOGGED_IN', $system->getLoggedInState()); //emulating RCMS LOGGED_IN state
require_once($system->getIndexModulePath()); //react to some module routes


if (XHPROF) {
    $xhprof_data = xhprof_disable();
    $xhprof_runs = new XHProfRuns_Default();
    $xhprof_run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_yalf");
    $xhprof_run_url = $xhProfLibsPath . '/xhprof_html/index.php?run=' . $xhprof_run_id . '&source=xhprof_yalf';
    $xhprof_frame = wf_tag('iframe', false, '', 'src="' . $xhprof_run_url . '" width="100%" height="750"') . wf_tag('iframe', true);
    $xhprof_link = wf_modal(wf_img_sized('skins/xhprof.png', __('XHPROF'), 20), 'XHProf current page results', $xhprof_frame, '', '1024', '768');
}


//web based renderer template load
if ($system->getRenderer() == 'WEB') {
    require_once($system->getSkinPath() . $system::SKIN_TEMPLATE_NAME);
}
