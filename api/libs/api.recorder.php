<?php

class Recorder {

    /**
     * Contains alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains binpaths.ini config as key=>value
     *
     * @var array
     */
    protected $binPaths = array();

    /**
     * Cameras instance placeholder
     *
     * @var object
     */
    protected $cameras = '';

    /**
     * Contains stardust process manager instance
     *
     * @var object
     */
    protected $stardust = '';

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
     * Contains cd command path
     *
     * @var string
     */
    protected $cdPath = '';

    /**
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * some creepy params here
     */
    protected $chunkTime = 60;
    protected $transportTemplate = '';
    protected $recordOpts = '';
    protected $chunksMask = '%Y-%m-%d_%H-%M-%S.mp4';

    /**
     * Some predefined stuff
     */
    const PID_PREFIX = 'RECORD_';

    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->setOptions();
        $this->initStardust();
        $this->initCameras();
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
        $this->binPaths = $ubillingConfig->getBinpaths();
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets required properties depends on config options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->ffmpgPath = $this->binPaths['FFMPG_PATH'];
        $this->cdPath = $this->binPaths['CD'];
        $this->chunkTime = $this->altCfg['RECORDER_CHUNK_TIME'];
        $this->transportTemplate = '-rtsp_transport tcp -f rtsp -i';
        $this->recordOpts = '-strict -2 -acodec copy -vcodec copy -f segment -segment_time ' . $this->chunkTime . ' -strftime 1 -segment_atclocktime 1 -segment_clocktime_offset 30 -reset_timestamps 1 -segment_format mp4';
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
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
     * Inits stardust process manager
     * 
     * @return void
     */
    protected function initStardust() {
        $this->stardust = new StarDust();
    }

    /**
     * Allocates path in storage for channel recording if required
     * 
     * @param string $storagePath
     * @param string $channel
     * 
     * @return string/bool
     */
    protected function initChannel($storagePath, $channel) {
        $result = false;
        $delimiter = '';
        if (!empty($storagePath) AND ! empty($channel)) {
            if (file_exists($storagePath)) {
                if (is_dir($storagePath)) {
                    if (is_writable($storagePath)) {
                        $pathLastChar = substr($storagePath, -1);
                        if ($pathLastChar != '/') {
                            $delimiter = '/';
                        }
                        $fullPath = $storagePath . $delimiter . $channel . '/';
                        //allocate channel dir
                        if (!file_exists($fullPath)) {
                            mkdir($fullPath, 0777);
                            chmod($fullPath, 0777);
                            log_register('RECORDER ALLOCATED STORAGE `' . $storagePath . '` CHANNEL `' . $channel . '`');
                        }

                        //seems ok?
                        if (is_writable($fullPath)) {
                            $result = $fullPath;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * 
     * @param int $cameraId
     * 
     * @return ???
     */
    public function runRecord($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCamerasData[$cameraId])) {
            $cameraData = $this->allCamerasData[$cameraId];
            $pid = self::PID_PREFIX . $cameraId;
            $this->stardust->setProcess($pid);
            if ($this->stardust->notRunning()) {
                if (zb_PingICMP($cameraData['CAMERA']['ip'])) {
                    $channelPath = $this->initChannel($cameraData['STORAGE']['path'], $cameraData['CAMERA']['channel']);
                    if ($channelPath) {
                        if ($cameraData['TEMPLATE']['MAIN_STREAM']) {
                            if ($cameraData['TEMPLATE']['PROTO'] == 'rtsp') {
                                $authString = $cameraData['CAMERA']['login'] . ':' . $cameraData['CAMERA']['password'] . '@';
                                $streamUrl = $cameraData['CAMERA']['ip'] . ':' . $cameraData['TEMPLATE']['RTSP_PORT'] . $cameraData['TEMPLATE']['MAIN_STREAM'];
                                $captureFullUrl = "'rtsp://" . $authString . $streamUrl . "' " . $this->recordOpts . ' ' . $this->chunksMask;
                                $captureCommand = $this->ffmpgPath . ' ' . $this->transportTemplate . ' ' . $captureFullUrl;
                                $fullCommand = 'cd ' . $channelPath . ' && ' . $captureCommand;

                                $this->stardust->start();
                                shell_exec($fullCommand);
                                $this->stardust->stop();
                            }
                        }
                    }
                }
            }
        }
    }

}
