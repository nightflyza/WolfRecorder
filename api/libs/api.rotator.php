<?php

/**
 * Performs chunks cleanup to prevent storages exhaust
 */
class Rotator {

    /**
     * Contains alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains binpaths config as key=>value
     *
     * @var array
     */
    protected $binPaths = array();

    /**
     * Contains percent of reserved space for each storage
     *
     * @var  int
     */
    protected $reservedSpacePercent = 10;

    /**
     * Storages instance placeholder.
     *
     * @var object
     */
    protected $storages = '';

    /**
     * Cameras instance placeholder
     *
     * @var object
     */
    protected $cameras = '';

    /**
     * Contains full cameras data as 
     *
     * @var array
     */
    protected $allCamerasData = array();

    public function __construct() {
        $this->loadConfigs();
        $this->initStorages();
        $this->initCameras();
    }

    /**
     * Loads all required configs
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->binPaths = $ubillingConfig->getBinpaths();
        $this->reservedSpacePercent = $this->altCfg['STORAGE_RESERVED_SPACE'];
    }

    /**
     * Inits storages into protected prop for further usage
     * 
     * @return void
     */
    protected function initStorages() {
        $this->storages = new Storages();
    }

    /**
     * Inits cameras into protected prop and loads its full data
     * 
     * @return void
     */
    protected function initCameras() {
        $this->cameras = new Cameras();
        $this->allCamerasData = $this->cameras->getAllCamerasFullData();
    }

    /**
     * Performs old chunks rotation for all cameras
     * 
     * @return void
     */
    public function run() {
        $allStorages = $this->storages->getAllStoragesData();
        if (!empty($allStorages)) {
            
        }
    }

}
