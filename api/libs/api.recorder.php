<?php

/**
 * Camera streams capture/recording implementation
 */
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
     * Storages instance placeholder.
     *
     * @var object
     */
    protected $storages = '';

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
     * Debugging mode flag
     *
     * @var bool
     */
    protected $debugFlag = false;

    /**
     * some creepy params here
     */
    protected $chunkTime = 60;
    protected $transportTemplate = '';
    protected $recordOpts = '';
    protected $audioCapture = '';
    protected $supressOutput = '';

    /**
     * Some predefined stuff
     */
    const PID_PREFIX = 'RECORD_';
    const CAPTURE_PID = 'CAPTURE';
    const WRAPPER = '/bin/wrapi';
    const CHUNKS_MASK = '%s';
    const CHUNKS_EXT = '.mp4';
    const DEBUG_LOG = 'exports/recorder_debug.log';

    /**
     * Dinosaurs are my best friends
     * Through thick and thin, until the very end
     * People tell me, do not pretend
     * Stop living in your made up world again
     * But the dinosaurs, they're real to me
     * They bring me up and make me happy
     * Hold on now, I think I see
     * A dinosaur wants to play with me
     */
    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->setOptions();
        $this->initStardust();
        $this->initStorages();
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
        $this->transportTemplate = '-stimeout 5000000 -loglevel error -rtsp_transport tcp -f rtsp -i';
        $this->recordOpts = '-strict -2 -vcodec copy -f segment -segment_time ' . $this->chunkTime . ' -strftime 1 -segment_atclocktime 1 -segment_clocktime_offset 30 -reset_timestamps 1 -segment_format mp4';
        $this->audioCapture = '-acodec copy' . ' ';
        $this->supressOutput = '';
        if (isset($this->altCfg['RECORDER_DEBUG'])) {
            if ($this->altCfg['RECORDER_DEBUG']) {
                $this->debugFlag = true;
            }
        }
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
     * Inits storages into protected prop for further usage
     * 
     * @return void
     */
    protected function initStorages() {
        $this->storages = new Storages();
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
     * Runs recording process of some camera
     * 
     * @param int $cameraId
     * 
     * @return void
     */
    public function runRecord($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCamerasData[$cameraId])) {
            $cameraData = $this->allCamerasData[$cameraId];
            if ($cameraData['CAMERA']['active']) {
                $allRunningRecorders = $this->getRunningRecorders();
                if (!isset($allRunningRecorders[$cameraId])) {
                    $pid = self::PID_PREFIX . $cameraId;
                    $this->stardust->setProcess($pid);
                    if ($this->stardust->notRunning()) {
                        if (zb_PingICMP($cameraData['CAMERA']['ip'])) {
                            $storageId = $cameraData['CAMERA']['storageid'];
                            $channel = $cameraData['CAMERA']['channel'];
                            $channelPath = $this->storages->initChannel($storageId, $channel);
                            if ($channelPath) {
                                if ($cameraData['TEMPLATE']['MAIN_STREAM']) {
                                    //rtsp proto capture
                                    if ($cameraData['TEMPLATE']['PROTO'] == 'rtsp') {
                                        $authString = $cameraData['CAMERA']['login'] . ':' . $cameraData['CAMERA']['password'] . '@';
                                        $streamUrl = $cameraData['CAMERA']['ip'] . ':' . $cameraData['TEMPLATE']['RTSP_PORT'] . $cameraData['TEMPLATE']['MAIN_STREAM'];
                                        $audioOpts = ($cameraData['TEMPLATE']['SOUND']) ? $this->audioCapture : '';
                                        $captureFullUrl = "'rtsp://" . $authString . $streamUrl . "' " . $audioOpts . $this->recordOpts . ' ' . self::CHUNKS_MASK . self::CHUNKS_EXT;
                                        $captureCommand = $this->ffmpgPath . ' ' . $this->transportTemplate . ' ' . $captureFullUrl . ' ' . $this->supressOutput;
                                        $fullCommand = 'cd ' . $channelPath . ' && ' . $captureCommand;

                                        $this->stardust->start();
                                        log_register('RECORDER STARTED [' . $cameraId . ']');
                                        //optional logging there
                                        if ($this->debugFlag) {
                                            file_put_contents(self::DEBUG_LOG, curdatetime() . ' START: ' . $fullCommand . PHP_EOL, FILE_APPEND);
                                        }
                                        shell_exec($fullCommand); //locks process till end
                                        $this->stardust->stop();
                                    }
                                } else {
                                    log_register('RECORDER FAILED [' . $cameraId . '] NO MAINSTREAM');
                                }
                            } else {
                                log_register('RECORDER FAILED [' . $cameraId . '] CHANNEL NOT EXISTS');
                            }
                        } else {
                            if ($this->debugFlag) {
                                log_register('RECORDER NOTSTARTED [' . $cameraId . '] CAMERA NOT ACCESSIBLE');
                            }
                        }
                    } else {
                        log_register('RECORDER NOTSTARTED [' . $cameraId . '] ALREADY RUNNING STARDUST');
                    }
                } else {
                    log_register('RECORDER NOTSTARTED [' . $cameraId . '] ALREADY RUNNING REALPROCESS');
                }
            } else {
                log_register('RECORDER NOTSTARTED [' . $cameraId . '] CAMERA DISABLED');
            }
        } else {
            log_register('RECORDER FAILED [' . $cameraId . '] CAMERA NOT EXISTS');
        }
    }

    /**
     * Returns all running recorders real process PID-s array as pid=>processString
     * 
     * @return array
     */
    protected function getRecordersPids() {
        $result = array();
        $command = $this->binPaths['PS'] . ' ax | ' . $this->binPaths['GREP'] . ' ' . $this->ffmpgPath . ' | ' . $this->binPaths['GREP'] . ' -v grep';
        $rawResult = shell_exec($command);
        if (!empty($rawResult)) {
            $rawResult = explodeRows($rawResult);
            foreach ($rawResult as $io => $eachLine) {
                $eachLine = trim($eachLine);
                $rawLine = $eachLine;
                $eachLine = explode(' ', $eachLine);
                if (isset($eachLine[0])) {
                    $eachPid = $eachLine[0];
                    if (is_numeric($eachPid)) {
                        //is this really capture process?
                        if (ispos($rawLine, $this->recordOpts)) {
                            $result[$eachPid] = $rawLine;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns running cameras recording processes as cameraId=>realPid
     * 
     * @return array
     */
    public function getRunningRecorders() {
        $result = array();
        if (!empty($this->allCamerasData)) {
            $recorderPids = $this->getRecordersPids();
            if (!empty($recorderPids)) {
                foreach ($this->allCamerasData as $eachCameraId => $eachCameraData) {
                    foreach ($recorderPids as $eachPid => $eachProcess) {
                        //looks familiar?
                        if (ispos($eachProcess, $eachCameraData['CAMERA']['ip']) AND ispos($eachProcess, $eachCameraData['CAMERA']['login'])) {
                            $result[$eachCameraId] = $eachPid;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Shutdowns recorder process if its running
     * 
     * @param int $cameraId
     * 
     * @return void
     */
    public function stopRecord($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        $allRunningRecorders = $this->getRunningRecorders();
        if (isset($allRunningRecorders[$cameraId])) {
            $count = 0;
            while (isset($allRunningRecorders[$cameraId])) {
                $count++;
                $command = $this->binPaths['SUDO'] . ' ' . $this->binPaths['KILL'] . ' ' . $allRunningRecorders[$cameraId];
                shell_exec($command);
                if ($this->debugFlag) {
                    file_put_contents(self::DEBUG_LOG, curdatetime() . ' STOP: ' . $command . PHP_EOL, FILE_APPEND);
                }
                $allRunningRecorders = $this->getRunningRecorders();
            }
            log_register('RECORDER STOPPED [' . $cameraId . '] ATTEMPT `' . $count . '`');
        }
    }

    /**
     * Runs recorder for selected camera in background
     * 
     * @param int $cameraId
     * 
     * @return void
     */
    public function runRecordBackground($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCamerasData[$cameraId])) {
            $cameraData = $this->allCamerasData[$cameraId];
            if ($cameraData['CAMERA']['active']) {
                $recordingProcess = new StarDust(self::PID_PREFIX . $cameraId);
                $allRunningRecorders = $this->getRunningRecorders();
                if (!isset($allRunningRecorders[$cameraId])) {
                    $recordingProcess->runBackgroundProcess(self::WRAPPER . ' "recherd&cameraid=' . $cameraId . '"', 1);
                }
            }
        }
    }

    /**
     * Runs all recorders for active cameras in background
     * 
     * @return void
     */
    public function captureAll() {
        $captureProcess = new StarDust(self::CAPTURE_PID);
        if ($captureProcess->notRunning()) {
            $captureProcess->start();
            if (!empty($this->allCamerasData)) {
                foreach ($this->allCamerasData as $io => $eachCamera) {
                    if ($eachCamera['CAMERA']['active']) {
                        $cameraId = $eachCamera['CAMERA']['id'];
                        $this->runRecordBackground($cameraId);
                    }
                }
            }
            $captureProcess->stop();
        }
    }

}
