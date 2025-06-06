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

    /**
     * Debugging mode flag
     *
     * @var bool
     */
    protected $debugFlag = false;

    /**
     * Use fast rotator for channels cleanup?
     *
     * @var bool
     */
    protected $fastFlag = false;

    /**
     * Other predefined stuff
     */
    const ROTATOR_PID = 'ROTATOR';
    const DEBUG_LOG = 'exports/rotator_debug.log';

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

        if (isset($this->altCfg['ROTATOR_DEBUG'])) {
            if ($this->altCfg['ROTATOR_DEBUG']) {
                $this->debugFlag = true;
            }
        }

        if (isset($this->altCfg['ROTATOR_FAST'])) {
            if ($this->altCfg['ROTATOR_FAST']) {
                $this->fastFlag = true;
            }
        }
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
        return ($result);
    }

    /**
     * Just deletes oldest chunk from some channel
     * 
     * @param int $storageId
     * @param string $channel
     * 
     * @return string/void
     */
    protected function flushChannelOldestChunk($storageId, $channel) {
        $result = '';
        $chunksList = $this->storages->getChannelChunks($storageId, $channel);
        if (!empty($chunksList)) {
            $chunksCount = sizeof($chunksList);
            //does not kill a single chunk. It may be the current recording.
            if ($chunksCount > 1) {
                $oldestChunk = reset($chunksList);
                unlink($oldestChunk);
                $result = $oldestChunk;
            }
        }
        return ($result);
    }

    /**
     * Checks, is channel locked by another background process or not?
     * 
     * @param string $channelId
     * 
     * @return bool
     */
    protected function channelNotLocked($channelId) {
        $result = true;
        $channelPid = new StarDust(Export::PID_EXPORT . $channelId);
        if ($channelPid->isRunning()) {
            $result = false;
        }
        return ($result);
    }

    /**
     * Cleanups channel chunks until total size will be less than specified
     * 
     * @param array $chunksList
     * @param int $expectedSize
     * 
     * @return array
     */
    protected function flushChunksBySize($chunksList, $expectedSize) {
        $bytesFree = 0;
        $chunksDeleted = 0;

        if ((!empty($chunksList)) and ($expectedSize > 0)) {
            foreach ($chunksList as $eachTimeStamp => $chunksData) {
                if ($bytesFree < $expectedSize) {
                    unlink($chunksData['path']);
                    $chunksDeleted++;
                    $bytesFree += $chunksData['size'];
                }
            }
        }

        $result = array('count' => $chunksDeleted, 'free' => $bytesFree);
        return ($result);
    }

    /**
     * Performs old chunks rotation for all cameras
     * 
     * @return void
     */
    public function run() {
        $rotatorProcess = new StarDust(self::ROTATOR_PID);
        if ($rotatorProcess->notRunning()) {
            $rotatorProcess->start();
            if (!empty($this->allStoragesData)) {
                foreach ($this->allStoragesData as $io => $eachStorage) {
                    $storageTotalSpace = disk_total_space($eachStorage['path']);
                    $storageFreeSpace = disk_free_space($eachStorage['path']);
                    $usedStorageSpace = $storageTotalSpace - $storageFreeSpace;
                    $maxUsagePercent = 100 - ($this->reservedSpacePercent);
                    $maxUsageSpace = zb_Percent($storageTotalSpace, $maxUsagePercent);
                    $mustBeFree = $storageTotalSpace - $maxUsageSpace;
                    $allChannelsSpace = 0;

                    //storage cleanup required?
                    if ($storageFreeSpace < $mustBeFree) {
                        $eachStorageChannels = $this->getStorageChannels($eachStorage['id']);
                        //this storage must be cleaned
                        if (!empty($eachStorageChannels)) {
                            //count of channels
                            $storageChannelsCount = sizeof($eachStorageChannels);
                            foreach ($eachStorageChannels as $eachChannel => $chanPath) {
                                $allChannelsSpace += $this->storages->getChannelSize($eachStorage['id'], $eachChannel);
                            }

                            $avgChanSize = $allChannelsSpace / $storageChannelsCount;
                            $usedBySystem = $usedStorageSpace - $allChannelsSpace;
                            //fair?
                            $maxChannelAllocSize = round((($storageFreeSpace - $mustBeFree) + $allChannelsSpace) / $storageChannelsCount);

                            foreach ($eachStorageChannels as $eachChannel => $chanPath) {
                                if ($this->channelNotLocked($eachChannel)) {
                                    //
                                    // Fast rotator here
                                    //
                                    if ($this->fastFlag) {
                                        $eachChannelChunksAlloc = $this->storages->getChunksAllocSpaces($eachStorage['id'], $eachChannel);
                                        $eachChannelSize = $this->storages->calcChunksListSize($eachChannelChunksAlloc);
                                        //this channel is exhausted his reserved size?
                                        if ($eachChannelSize > $maxChannelAllocSize) {
                                            if ($this->debugFlag) {
                                                file_put_contents(self::DEBUG_LOG, curdatetime() . ' ' . wr_convertSize($eachChannelSize) . ' > OF ' . wr_convertSize($maxChannelAllocSize) . ' ' . $eachChannel . PHP_EOL, FILE_APPEND);
                                            }
                                            $requiredToFree = $eachChannelSize - $maxChannelAllocSize;
                                            $cleanResult = $this->flushChunksBySize($eachChannelChunksAlloc, $requiredToFree);
                                            if ($this->debugFlag) {
                                                file_put_contents(self::DEBUG_LOG, curdatetime() . ' ' . wr_convertSize($cleanResult['free']) . ' CLEANED IN ' . $eachChannel . ' DELETED ' . $cleanResult['count'] . ' CHUNKS' . PHP_EOL, FILE_APPEND);
                                            }
                                        } else {
                                            //and there some rotation skips logging
                                            if ($this->debugFlag) {
                                                file_put_contents(self::DEBUG_LOG, curdatetime() . ' ' . wr_convertSize($eachChannelSize) . ' < OF ' . wr_convertSize($maxChannelAllocSize) . ' ' . $eachChannel . PHP_EOL, FILE_APPEND);
                                            }
                                        }
                                    } else {
                                        //
                                        // Stable rotator model
                                        //
                                        $eachChannelSize = $this->storages->getChannelSize($eachStorage['id'], $eachChannel);
                                        //this channel is exhausted his reserved size?
                                        if ($eachChannelSize > $maxChannelAllocSize) {
                                            while ($eachChannelSize > $maxChannelAllocSize) {
                                                $this->flushChannelOldestChunk($eachStorage['id'], $eachChannel);
                                                $eachChannelSize = $this->storages->getChannelSize($eachStorage['id'], $eachChannel);
                                                //some debug logging here
                                                if ($this->debugFlag) {
                                                    file_put_contents(self::DEBUG_LOG, curdatetime() . ' ' . wr_convertSize($eachChannelSize) . ' > OF ' . wr_convertSize($maxChannelAllocSize) . ' ' . $eachChannel . PHP_EOL, FILE_APPEND);
                                                }
                                            }
                                        } else {
                                            //and there some rotation skips logging
                                            if ($this->debugFlag) {
                                                file_put_contents(self::DEBUG_LOG, curdatetime() . ' ' . wr_convertSize($eachChannelSize) . ' < OF ' . wr_convertSize($maxChannelAllocSize) . ' ' . $eachChannel . PHP_EOL, FILE_APPEND);
                                            }
                                        }
                                    }
                                } else {
                                    //and there some rotation skips on export logging
                                    if ($this->debugFlag) {
                                        file_put_contents(self::DEBUG_LOG, curdatetime() . ' SKIPPED LOCKED BY EXPORT ' . $eachChannel . PHP_EOL, FILE_APPEND);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $rotatorProcess->stop();
        }
    }
}
