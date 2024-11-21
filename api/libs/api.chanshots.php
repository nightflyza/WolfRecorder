<?php

/**
 * Channel screenshots service implementation
 */
class ChanShots {

    /**
     * Contains binpaths.ini config as key=>value
     *
     * @var array
     */
    protected $binPaths = array();

    /**
     * Contains alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Cameras instance placeholder
     *
     * @var object
     */
    protected $cameras = '';

    /**
     * Recorder instance placeholder
     *
     * @var object
     */
    protected $recorder = '';

    /**
     * Storages instance placeholder.
     *
     * @var object
     */
    protected $storages = '';

    /**
     * Contains full cameras data as 
     *
     * @var array
     */
    protected $allCamerasData = array();

    /**
     * Contains camera running recorders state as cameraId=>PID
     *
     * @var array
     */
    protected $cameraRecordersRunning = array();

    /**
     * Contains ffmpeg binary path
     *
     * @var string
     */
    protected $ffmpgPath = '';

    /**
     * Contains basic screenshots path
     *
     * @var string
     */
    protected $screenshotsPath = '';

    /**
     * Default chunk time offset to screenshot
     *
     * @var string
     */
    protected $timeOffset = '00:00:01';

    /**
     * Contains default screenshot options
     *
     * @var string
     */
    protected $screenshotOpts = '-loglevel error -frames:v 1 -q:v 15';

    /**
     * PixelCraft instance for further chanshots processing
     *
     * @var object
     */
    protected $pixelCraft = '';

    /**
     * Channel screenshots validation flag
     *
     * @var bool
     */
    protected $shotsValidationFlag = false;

    /**
     * Channel screenshots embedding flag
     *
     * @var bool
     */
    protected $shotsEmbedFlag = false;

    /**
     * Contains base64 encoded latest checked channel shot
     *
     * @var string
     */
    protected $lastCheckedShot = '';

     /**
     * Contains embedded channel shots watermark path
     *
     * @var string
     */
    protected $shotsWatermarkPath = '';

    public function __construct() {
        $this->loadConfigs();
        $this->initPixelCraft();
    }

    /**
     * Some predefined paths here
     */
    const SHOTS_SUBDIR = 'chanshots/';
    const SHOTS_EXT = '.jpg';
    const CHANSHOTS_PID = 'CHANSHOTS';
    /**
     * predefined shots paths
     */
    const ERR_NOSIG = 'skins/nosignal.gif';
    const ERR_CORRUPT = 'skins/error.gif';
    const ERR_DISABLD = 'skins/chanblock.gif';

    /**
     * Loads some required configs
     * 
     * @global $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->binPaths = $ubillingConfig->getBinpaths();
        $this->altCfg = $ubillingConfig->getAlter();
        $this->ffmpgPath = $this->binPaths['FFMPG_PATH'];
        $this->screenshotsPath = Storages::PATH_HOWL . self::SHOTS_SUBDIR;
        $this->shotsValidationFlag = $ubillingConfig->getAlterParam('CHANSHOTS_VALIDATION');
        $this->shotsEmbedFlag = $ubillingConfig->getAlterParam('CHANSHOTS_EMBED');
        $this->shotsWatermarkPath = $ubillingConfig->getAlterParam('CHANSHOTS_WATERMARK');
    }

    /**
     * Inits PixelCraft object instance
     *
     * @return void
     */
    protected function initPixelCraft() {
        $this->pixelCraft = new PixelCraft();
        //loading watermark once
        if (!empty($this->shotsWatermarkPath)) {
            $this->pixelCraft->loadWatermark($this->shotsWatermarkPath);
        }
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
     * Inits recorder instance for detecting is camera alive or not?
     * 
     * @return void
     */
    protected function initRecorder() {
        $this->recorder = new Recorder();
        $this->cameraRecordersRunning = $this->recorder->getRunningRecorders();
    }

    /**
     * Returns screenshots base path
     * 
     * @return string
     */
    public function getScreenshotsPath() {
        return ($this->screenshotsPath);
    }

    /**
     * Checks if a channel screenshot is valid.
     *
     * @param string $channelScreenshot The path to the channel screenshot file.
     * @return bool Returns true if the channel screenshot is valid, false otherwise.
     */
    public function isChannelScreenshotValid($channelScreenshot) {
        $result = true;
        if ($this->shotsValidationFlag) {
            $result = false;
            if (file_exists($channelScreenshot)) {
                $imageValid = $this->pixelCraft->isImageValid($channelScreenshot);
                if ($imageValid) {
                    $result = true;
                    if ($this->shotsEmbedFlag) {
                        $this->embedScreenshotProcessing($channelScreenshot);
                    }
                } else {
                    if ($this->shotsEmbedFlag) {
                        $this->lastCheckedShot = '';
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Embedded channel screenshot processing
     *
     * @param string $channelScreenshot
     * 
     * @return void
     */
    protected function embedScreenshotProcessing($channelScreenshot) {
        if ($this->shotsEmbedFlag) {
            $this->pixelCraft->loadImage($channelScreenshot);
            if (!empty($this->shotsWatermarkPath)) {
                $this->pixelCraft->drawWatermark(false, 0, 0);
            }
            $this->lastCheckedShot = $this->pixelCraft->getImageBase('jpeg', true);
        }
    }

    /**
     * Returns lastCheckedShot property content
     *
     * @return string
     */
    public function getLastCheckedShot() {
        return ($this->lastCheckedShot);
    }

    /**
     * Returns channel latest screenshot path
     * 
     * @param string $channelId
     * 
     * @return string/void
     */
    public function getChannelScreenShot($channelId) {
        $result = '';
        $channelId = ubRouting::filters($channelId, 'mres');
        if (file_exists($this->screenshotsPath)) {
            $screenshotName = $channelId . self::SHOTS_EXT;
            $fullPath = $this->screenshotsPath . $screenshotName;
            if (file_exists($fullPath)) {
                $result = $fullPath;
            }
        }
        return ($result);
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
     * Allocates screenshots path, returns it if its writable
     * 
     * @return string/void on error
     */
    protected function allocateScreenshotsPath() {
        $result = '';
        if (!file_exists($this->screenshotsPath)) {
            mkdir($this->screenshotsPath, 0777);
            chmod($this->screenshotsPath, 0777);
            log_register('CHANSHOTS ALLOCATED `' . $this->screenshotsPath . '`');
        } else {
            $result = $this->screenshotsPath;
        }
        return ($result);
    }

    /**
     * Cleanups old screenshot if it exists
     * 
     * @param string $channel
     * 
     * @return void
     */
    protected function flushOldScreenshot($channel) {
        if (!empty($channel)) {
            $fileName = $channel . self::SHOTS_EXT;
            $fullScreenShotPath = $this->screenshotsPath . $fileName;
            if (file_exists($fullScreenShotPath)) {
                unlink($fullScreenShotPath);
            }
        }
    }

    /**
     * Tooks screenshot from some channel chunk
     * 
     * @param string $chunkPath
     * @param string $channel
     * 
     * @return string
     */
    protected function takeChunkScreenshot($chunkPath, $channel) {
        $result = '';
        if (!empty($channel) and file_exists($chunkPath)) {
            $fileName = $channel . self::SHOTS_EXT;
            $fullScreenShotPath = $this->screenshotsPath . $fileName;
            //old screenshot cleanup
            $this->flushOldScreenshot($channel);
            //taking new screenshot for this channel
            $command = $this->ffmpgPath . ' -ss ' . $this->timeOffset . ' -i ' . $chunkPath . ' ' . $this->screenshotOpts . ' ' . $fullScreenShotPath;
            $result = shell_exec($command);
        }
        return ($result);
    }

    /**
     * Performs capturing screeenshots from all active camera channels
     * 
     * @return void
     */
    public function run() {
        $process = new StarDust(self::CHANSHOTS_PID);
        if ($process->notRunning()) {
            $process->start();
            //preload required objects
            $this->initStorages();
            $this->initCameras();
            //any cameras here?
            if (!empty($this->allCamerasData)) {
                $this->initRecorder();
                $this->allocateScreenshotsPath();
                foreach ($this->allCamerasData as $eachCamId => $eachCameraData) {
                    if ($eachCameraData['CAMERA']['active']) {
                        $channel = $eachCameraData['CAMERA']['channel'];
                        $storageId = $eachCameraData['CAMERA']['storageid'];
                        //camera recorder now running?
                        if (isset($this->cameraRecordersRunning[$eachCamId])) {
                            $allCameraChunks = $this->storages->getChannelChunks($storageId, $channel);
                            $chunksCount = sizeof($allCameraChunks);
                            //dont shot single chunk - it may be unfinished
                            if ($chunksCount > 1) {
                                $lastChunk = array_pop($allCameraChunks);
                                $secondLastChunk = array_pop($allCameraChunks);
                                //taking screenshot from second last channel chunk
                                $this->takeChunkScreenshot($secondLastChunk, $channel);
                            }
                        } else {
                            //if no recorder now running just flush channel scrreenshot
                            $this->flushOldScreenshot($channel);
                        }
                    }
                }
            }
            $process->stop();
        }
    }

}
