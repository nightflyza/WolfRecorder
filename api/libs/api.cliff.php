<?php

/**
 * ffmpeg version-specific CLI options wrapper
 */
class CliFF {

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
     * Contains ffmpeg binary path
     *
     * @var string
     */
    protected $ffmpgPath = '';

    /**
     * Contains ffprobe binary path. Assumed to be in the same directory as ffmpeg
     *
     * @var string
     */
    protected $ffprobePath = '';

    /**
     * Default chunk time in seconds
     * 
     * @var int
     */
    protected $chunkTime = 60;

    /**
     * Contains motion detection threshold
     *
     * @var int
     */
    protected $moDetThreshold = 2;

    /**
     * Contains motion detection time scale
     *
     * @var int
     */
    protected $moDetTimeScale = 15;

    /**
     * Contains motion detection options string
     *
     * @var string
     */
    protected $moDetOpts = '';

    /**
     * Some CLI option templates
     */
    protected $transportTemplate = '';
    protected $recordOpts = '';
    protected $audioCapture = '';
    protected $liveOptsPrefix = '';
    protected $liveOptsSuffix = '';
    protected $codecInfoOpts = '';

    /**
     * Current instance ffmpeg version as three-digits integer
     * 
     * @var int
     */
    protected $ffmpegVersion = 440;

    /**
     * Creates new CliFF instance
     */
    public function __construct() {
        $this->loadConfigs();
        $this->setVersion();
        $this->setTemplates();
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
        $this->ffmpgPath = $this->binPaths['FFMPG_PATH'];
        $this->chunkTime = $this->altCfg['RECORDER_CHUNK_TIME'];
        $this->ffprobePath = str_replace('ffmpeg', 'ffprobe', $this->ffmpgPath);
    }

    /**
     * Detects current instance ffmpeg version and sets it into property
     * 
     * @return void
     */
    protected function setVersion() {
        $command = $this->ffmpgPath . ' -version';
        $rawResult = shell_exec($command);
        if (!empty($rawResult)) {
            $rawResult = explodeRows($rawResult);
            if (isset($rawResult[0])) {
                $firstLine = $rawResult[0];
                if (!empty($firstLine) and ispos($firstLine, 'ffmpeg version')) {
                    $rawVersion = substr($firstLine, 15, 5);
                    if (!empty($rawVersion)) {
                        $this->ffmpegVersion = ubRouting::filters($rawVersion, 'int');
                    }
                }
            }
        }
    }

    /**
     * Sets some ffmpeg cli options templates
     * 
     * @return void
     */
    protected function setTemplates() {
        //defaults which works with ffmpeg 4.4.2
        $this->transportTemplate = '-stimeout 5000000 -loglevel error -rtsp_transport tcp -f rtsp -i';
        $this->recordOpts = '-strict -2 -vcodec copy -f segment -segment_time ' . $this->chunkTime . ' -strftime 1 -segment_atclocktime 1 -segment_clocktime_offset 30 -reset_timestamps 1 -segment_format mp4';
        $this->audioCapture = '-acodec copy' . ' ';
        $this->liveOptsPrefix = '-stimeout 5000000 -loglevel error -rtsp_transport tcp -f rtsp -i';
        $this->liveOptsSuffix = '-strict -2 -vcodec copy -hls_wrap 10';
        $this->codecInfoOpts = '-v quiet -print_format json -show_format -show_streams';

        // burn cpu burn! lol
        //$this->liveOptsSuffix = '-strict -2 -vcodec libx264 -preset ultrafast -hls_wrap 10';

        //some ffmpeg >=5.0 opts
        if ($this->ffmpegVersion >= 500) {
            //stimeout option deprecated and replaced with timeout
            $this->transportTemplate = '-timeout 5000000 -loglevel error -rtsp_transport tcp -f rtsp -i';
            $this->liveOptsPrefix = '-timeout 5000000 -loglevel error -rtsp_transport tcp -f rtsp -i';
            //hls_wrap option deprecated too
            $this->liveOptsSuffix = '-strict -2 -vcodec copy -hls_flags delete_segments -hls_list_size 10 -segment_wrap 10';
        }
    }

    /**
     * Returns RTSP transport template
     * 
     * @return string
     */
    public function getTransportTemplate() {
        return ($this->transportTemplate);
    }

    /**
     * Returns recorder options template
     * 
     * @return string
     */
    public function getRecordOpts() {
        return ($this->recordOpts);
    }

    /**
     * Returns optional audio capture template
     * 
     * @return string
     */
    public function getAudioCapture() {
        return ($this->audioCapture);
    }

    /**
     * Returns live-preview hls stream prefix template
     * 
     * @return string
     */
    public function getLiveOptsPrefix() {
        return ($this->liveOptsPrefix);
    }

    /**
     * Returns live-preview hls stream suffix template
     * 
     * @return string
     */
    public function getLiveOptsSuffix() {
        return ($this->liveOptsSuffix);
    }

    /**
     * Returns ffmpeg binary path
     *
     * @return string
     */
    public function getFFmpegPath() {
        return ($this->ffmpgPath);
    }

    /**
     * Returns ffprobe binary path
     *
     * @return string
     */
    public function getFFprobePath() {
        return ($this->ffprobePath);
    }

    /**
     * Returns codec info options
     *
     * @return string
     */
    public function getCodecInfoOpts() {
        return ($this->codecInfoOpts);
    }

    /**
     * Sets alternate motion detection parameters
     *
     * @param int $threshold motion detection sensitivity 1..100
     * @param int $timeScale Change the PTS of the input frames (x)
     * @return void
     */
    public function setMoDetParams($threshold=2,$timeScale=15) {
        $this->moDetThreshold=ubRouting::filters($threshold,'int');
        $this->moDetTimeScale=ubRouting::filters($timeScale,'int');
    }

    /**
     * Sets and returns motion detection options
     *
     * @return string
     */
    public function getMoDetOpts() {
        $this->moDetOpts = '-loglevel error -vf "select=gt(scene\,' . round($this->moDetThreshold / 100, 3) . '),setpts=N/(' . $this->moDetTimeScale . '*TB)" ';
        return ($this->moDetOpts);
    }
}
