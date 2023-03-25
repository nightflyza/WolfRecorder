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
     * Contains full cameras data as cameraId=>fullCameraData
     *
     * @var array
     */
    protected $allCamerasData = array();

    /**
     * Contains all storages data as storageId=>storageData
     *
     * @var array
     */
    protected $allStoragesData = array();

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
        $this->allStoragesData = $this->storages->getAllStoragesData();
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
     * Returns existing channels list available in storage depends of camera setup
     * 
     * @return array
     */
    protected function getStorageChannels($storageId) {
        $result = array();
        if (isset($this->allStoragesData[$storageId])) {
            if (!empty($this->allCamerasData)) {
                foreach ($this->allCamerasData as $io => $eachCameraData) {
                    if ($eachCameraData['CAMERA']['storageid'] == $storageId) {
                        $storagePath = $eachCameraData['STORAGE']['path'];
                        $storagePathLastChar = substr($storagePath, 0, -1);
                        if ($storagePathLastChar != '/') {
                            $storagePath = $storagePath . '/';
                        }
                        $channelName = $eachCameraData['CAMERA']['channel'];
                        $channelFullPath = $storagePath . $channelName;
                        if (file_exists($channelFullPath)) {
                            if (is_writable($channelFullPath)) {
                                $result[$channelName] = $channelFullPath;
                            }
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Performs old chunks rotation for all cameras
     * 
     * @return void
     */
    public function run() {
        if (!empty($this->allStoragesData)) {
            foreach ($this->allStoragesData as $io => $eachStorage) {
                $storageTotalSpace = disk_total_space($eachStorage['path']);
                $storageFreeSpace = disk_free_space($eachStorage['path']);
                $storageFreePercent = zb_PercentValue($storageTotalSpace, $storageFreeSpace);

                //cleanup required?
                if ($storageFreePercent <= $this->reservedSpacePercent) {
                    $eachStorageChannels = $this->getStorageChannels($eachStorage['id']);
                    if (!empty($eachStorageChannels)) {
                        debarr($eachStorageChannels);
                    }
                }
            }
        }
    }

}
