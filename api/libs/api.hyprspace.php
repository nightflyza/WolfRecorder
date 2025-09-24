<?php

class HyprSpace {
    /**
     * Contains alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains hyprspace config option
     *
     * @var bool
     */
    protected $hyprSpaceFlag = false;

    /**
     * Contains hyprspace flag that indicates that hyprspace is writable and in use
     *
     * @var bool
     */
    protected $inUse = false;

    /**
     * FS mountpoint where records is stored
     *
     * @var string
     */
    protected $recordsMountpoint = '/';

    /**
     * Contains default path for user recordings accessible from web
     *
     * @var string
     */
    protected $pathDefault = 'howl/recdl/';

    /**
     * Directory name to store videos in hyprspace with trailing slash
     *
     * @var string
     */
    protected $dirHyprSpace = 'recdl/';

    /**
     * Contains default hyprspace mountpoint path without trailing slash
     *
     * @var string
     */
    protected $pathHyprSpace = '/hyprspace';

    /**
     * Symlink name to be accessible via web
     *
     * @var string
     */
    protected $howlLink = 'hyprspace';

    /**
     * Contains web accessible base URL for user recordings
     *
     * @var string
     */
    protected $urlWeb = '';

    /**
     * Contains path for saving users records.
     *
     * @var string
     */
    protected $pathRecords = '';

    /**
     * other predefined stuff here
     */
    const OPTION_HYPRSPACE = 'HYPRSPACE_ENABLED';


    public function __construct() {
        $this->loadConfigs();
        $this->setOptions();
    }

    /**
     * Loads some required configs
     * 
     * @global $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets required properties depends on config options
     * 
     * @return void
     */
    protected function setOptions() {
        //default path here
        $this->pathRecords = $this->pathDefault;
        $this->urlWeb = $this->pathDefault;

        //setting hspace flag
        if (isset($this->altCfg[self::OPTION_HYPRSPACE])) {
            if ($this->altCfg[self::OPTION_HYPRSPACE]) {
                $this->hyprSpaceFlag = true;
            }
        }

        //checking hyprspace path
        if ($this->hyprSpaceFlag) {
            if (file_exists($this->pathHyprSpace)) {
                $fullHspacePath = $this->pathHyprSpace . '/' . $this->dirHyprSpace;
                if ($this->checkPath($fullHspacePath)) {
                    $this->inUse = true;
                    $this->recordsMountpoint = $this->pathHyprSpace;
                    $this->pathRecords = $fullHspacePath;
                    $this->urlWeb = 'howl/' . $this->howlLink . '/';
                }
            }
        }
    }

    /**
     * Prepares per-user recordings space
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function prepareRecordingsDir($userLogin = '') {
        $result = '';

        //allocating user dir
        if (!empty($userLogin) and !empty($this->pathRecords)) {
            $fullUserPath = $this->pathRecords . $userLogin;
            //base recordings path
            if (!file_exists($this->pathRecords)) {
                //creating base path
                mkdir($this->pathRecords, 0777);
                chmod($this->pathRecords, 0777);
            }

            if (!file_exists($fullUserPath)) {
                //and per-user path
                mkdir($fullUserPath, 0777);
                chmod($fullUserPath, 0777);
            }

            if (file_exists($fullUserPath)) {
                $result = $fullUserPath . '/'; //with ending slash
            }

            //symlinking hyprspace to howl if required
            if ($this->inUse) {
                $howlLink = 'howl/' . $this->howlLink; //full web-path in howl
                if (!file_exists($howlLink)) {
                    symlink($this->pathRecords, $howlLink);
                    log_register('HYPRSPACE LINKED `' . $this->pathRecords . '` TO `' . $howlLink . '`');
                }
            }
        }

        return ($result);
    }


    /**
     * Returns count bytes count allowed to each user to store his records
     * 
     * @return int
     */
    public function getUserMaxSpace() {
        $result = 0;
        $storageTotalSpace = disk_total_space($this->recordsMountpoint);
        if (isset($this->altCfg['EXPORTS_RESERVED_SPACE'])) {
            $maxUsagePercent = 100 - ($this->altCfg['EXPORTS_RESERVED_SPACE']); // explict value
        } else {
            $maxUsagePercent = 100 - ($this->altCfg['STORAGE_RESERVED_SPACE'] / 2); // half of reserved space
        }
        $maxUsageSpace = zb_Percent($storageTotalSpace, $maxUsagePercent);
        $mustBeFree = $storageTotalSpace - $maxUsageSpace;
        $usersCount = $this->getUserCount();
        if ($usersCount > 0) {
            $result = $mustBeFree / $usersCount;
        }
        return ($result);
    }

    /**
     * Checks is some path exists, valid and writable?
     * 
     * @param string $path
     * 
     * @return bool
     */
    protected function checkPath($path) {
        $result = false;
        if (file_exists($path)) {
            if (is_dir($path)) {
                if (is_writable($path)) {
                    $result = true;
                }
            }
        }
        return ($result);
    }


    /**
     * Returns count of users registered in system
     * 
     * @return int
     */
    public function getUserCount() {
        $result = 0;
        $allUsers = rcms_scandir(USERS_PATH);
        if (!empty($allUsers)) {
            $result = sizeof($allUsers);
        }
        return ($result);
    }

    /**
     * Returns currently used FS path to save user recoreds
     *
     * @return string
     */
    public function getPathRecords() {
        return ($this->pathRecords);
    }

    /**
     * Returns user recordings web accessible dir name like howl/recdl/ or howl/hyprspace/
     *
     * @param string $userLogin
     * 
     * @return string
     */
    public function getUrlRecords($userLogin = '') {
        $result = $this->urlWeb;
        if (!empty($userLogin)) {
            $result .=  $userLogin . '/';
        }
        return ($result);
    }

    /**
     * Returns mountpoint where exported records is stored
     *
     * @return string
     */
    public function getMountpointRecords() {
        return ($this->recordsMountpoint);
    }

    /**
     * Returns current hyprspace inUse flag state
     *
     * @return bool
     */
    public function isInUse() {
        return ($this->inUse);
    }
}
