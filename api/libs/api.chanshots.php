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

    public function __construct() {
        $this->loadConfigs();
    }

    /**
     * Some predefined paths here
     */
    const SHOTS_SUBDIR = 'chanshots/';
    const SHOTS_EXT = '.jpg';
    const CHANSHOTS_PID = 'CHANSHOTS';

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
     * Returns screenshots base path
     * 
     * @return string
     */
    public function getScreenshotsPath() {
        return($this->screenshotsPath);
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
        return($result);
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
        return($result);
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
        if (!empty($channel) AND file_exists($chunkPath)) {
            $fileName = $channel . self::SHOTS_EXT;
            $fullScreenShotPath = $this->screenshotsPath . $fileName;
            //old screenshot cleanup
            if (file_exists($fullScreenShotPath)) {
                unlink($fullScreenShotPath);
            }
            $command = $this->ffmpgPath . ' -ss ' . $this->timeOffset . ' -i ' . $chunkPath . ' ' . $this->screenshotOpts . ' ' . $fullScreenShotPath;
            $result = shell_exec($command);
        }
        return($result);
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
                $this->allocateScreenshotsPath();
                foreach ($this->allCamerasData as $eachCamId => $eachCameraData) {
                    if ($eachCameraData['CAMERA']['active']) {
                        $channel = $eachCameraData['CAMERA']['channel'];
                        $storageId = $eachCameraData['CAMERA']['storageid'];
                        $allCameraChunks = $this->storages->getChannelChunks($storageId, $channel);
                        $chunksCount = sizeof($allCameraChunks);
                        //dont shot single chunk - it may be unfinished
                        if ($chunksCount > 1) {
                            $lastChunk = array_pop($allCameraChunks);
                            $secondLastChunk = array_pop($allCameraChunks);
                            //taking screenshot from second last channel chunk
                            $this->takeChunkScreenshot($secondLastChunk, $channel);
                        }
                    }
                }
            }
            $process->stop();
        }
    }

    /**
     * Renders small channel preview for camera lists
     * 
     * @param string $channel
     * @param string $screenshot
     * 
     * @return string
     */
    public function renderListBox($channel, $screenshot) {
        $result = wf_tag('style');
        $result .= '  .preview' . $channel . ' {
                position: relative;
                margin-right: 10px;
               }
               
              .preview' . $channel . ' img {
                   object-fit: cover;
                   width: 124px;
                   height: 75px;
                  }
               ';
        $result .= wf_tag('style', true);
        $result .= wf_tag('span', false, 'preview' . $channel);
        $result .= wf_tag('img', false, 'preview' . $channel, 'src="' . $screenshot . '" style="width:124px;"');
        $result .= wf_tag('span', true);
        return($result);
    }

}
