<?php

/**
 * Live cams implementation
 */
class LiveCams {

    /**
     * Chanshots instance placeholder
     *
     * @var object
     */
    protected $chanshots = '';

    /**
     * Cameras instance placeholder
     *
     * @var  object
     */
    protected $cameras = '';

    /**
     * Contains all available cameras data as id=>camFullData
     *
     * @var array
     */
    protected $allCamerasData = array();

    /**
     * ACL instance placeholder
     *
     * @var object
     */
    protected $acl = '';

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
     * Contains player width by default
     *
     * @var string
     */
    protected $playerWidth = '80%';

    /**
     * Contains system messages helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains stardust process manager instance
     *
     * @var object
     */
    protected $stardust = '';

    /**
     * Contains streams base path for each channel
     *
     * @var string
     */
    protected $streamsPath = '';

    /**
     * Live stream basic options
     *
     * @var string
     */
    protected $liveOptsPrefix = '';

    /**
     * Live stream basic options
     *
     * @var string
     */
    protected $liveOptsSuffix = '';

    /**
     * CliFF instance placeholder
     * 
     * @var object
     */
    protected $cliff = '';

    /**
     * other predefined stuff like routes
     */
    const PID_PREFIX = 'LIVE_';
    const STREAMS_SUBDIR = 'livestreams/';
    const STREAM_PLAYLIST = 'stream.m3u8';
    const URL_ME = '?module=livecams';
    const URL_PSEUDOSTREAM = '?module=pseudostream';
    const ROUTE_VIEW = 'livechannel';
    const ROUTE_PSEUDOLIVE = 'live';
    const WRAPPER = '/bin/wrapi';

    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->initCliff();
        $this->setOptions();
        $this->initCameras();
        $this->initChanshots();
        $this->initAcl();
        $this->initStardust();
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
     * Inits ffmpeg CLI wrapper
     * 
     * @return void
     */
    protected function initCliff() {
        $this->cliff = new CliFF();
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
    }

    /**
     * Sets required properties depends on config options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->ffmpgPath = $this->binPaths['FFMPG_PATH'];
        $this->liveOptsPrefix = $this->cliff->getLiveOptsPrefix();
        $this->liveOptsSuffix = $this->cliff->getLiveOptsSuffix();
        $this->streamsPath = Storages::PATH_HOWL . self::STREAMS_SUBDIR;
    }

    /**
     * Inits chanshots instance for further usage
     * 
     * @return void
     */
    protected function initChanshots() {
        $this->chanshots = new ChanShots();
    }

    /**
     * Inits StarDust process manager
     * 
     * @return void
     */
    protected function initStardust() {
        $this->stardust = new StarDust();
    }

    /**
     * Inits ACL instance
     * 
     * @return void
     */
    protected function initAcl() {
        $this->acl = new ACL();
    }

    /**
     * Inits cameras instance and loads camera full data
     * 
     * @return void
     */
    protected function initCameras() {
        $this->cameras = new Cameras();
        $this->allCamerasData = $this->cameras->getAllCamerasFullData();
    }

    /**
     * Lists available cameras
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        if ($this->acl->haveCamsAssigned()) {
            if (!empty($this->allCamerasData)) {
                $style = 'style="float: left; margin: 5px;"';
                $result .= wf_tag('div');
                foreach ($this->allCamerasData as $eachCameraId => $eachCameraData) {
                    if ($this->acl->isMyCamera($eachCameraId)) {
                        $cameraChannel = $eachCameraData['CAMERA']['channel'];
                        $channelScreenshot = $this->chanshots->getChannelScreenShot($cameraChannel);
                        $cameraLabel = $this->cameras->getCameraComment($cameraChannel);
                        if (empty($channelScreenshot)) {
                            $channelScreenshot = 'skins/nosignal.gif';
                        }

                        if (!$eachCameraData['CAMERA']['active']) {
                            $channelScreenshot = 'skins/chanblock.gif';
                        }
                        $result .= wf_tag('div', false, '', $style);
                        $channelUrl = self::URL_ME . '&' . self::ROUTE_VIEW . '=' . $cameraChannel;
                        $channelImage = wf_img($channelScreenshot, $cameraLabel, 'width: 480px; height: 270px;  object-fit: cover;');
                        $channelLink = wf_Link($channelUrl, $channelImage);
                        $result .= $channelLink;
                        $result .= wf_tag('div', true);
                    }
                }
                $result .= wf_tag('div', true);
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('No assigned cameras to show'), 'warning');
        }
        return($result);
    }

    /**
     * Lists available cameras live-wall
     * 
     * @return string
     */
    public function renderLiveWall() {
        $result = '';
        if ($this->acl->haveCamsAssigned()) {
            if (!empty($this->allCamerasData)) {
                $style = 'style="float: left; margin: 5px; min-height:280px; min-width:400px; border: 0px solid blue;"';
                $result .= wf_tag('div');
                foreach ($this->allCamerasData as $eachCameraId => $eachCameraData) {
                    if ($this->acl->isMyCamera($eachCameraId)) {
                        $viewableFlag = true;
                        $cameraChannel = $eachCameraData['CAMERA']['channel'];
                        $cameraId=$eachCameraData['CAMERA']['id'];
                        $channelScreenshot = $this->chanshots->getChannelScreenShot($cameraChannel);
                        $cameraLabel = $this->cameras->getCameraComment($cameraChannel);
                        if (empty($channelScreenshot)) {
                            $channelScreenshot = 'skins/nosignal.gif';
                            $viewableFlag = false;
                        }

                        if (!$eachCameraData['CAMERA']['active']) {
                            $channelScreenshot = 'skins/chanblock.gif';
                            $viewableFlag = false;
                        }
                        $result .= wf_tag('div', false, '', $style);
                        if ($viewableFlag) {
                            $streamUrl = $this->getStreamUrl($cameraChannel);
                            if ($streamUrl) {
                                //seems live stream now live
                                $playerId = 'liveplayer_' . $cameraChannel;
                                $player = new Player('400px', true);
                                $player->setPlayerLib('w5');
                                $result .= $player->renderLivePlayer($streamUrl, $playerId);
                                $result .= $this->renderKeepAliveCallback($cameraId);
                            } else {
                                $result .= $this->messages->getStyledMessage(__('Oh no') . ': ' . __('No such live stream'), 'error');
                            }
                        } else {
                            $channelUrl = self::URL_ME . '&' . self::ROUTE_VIEW . '=' . $cameraChannel;
                            $channelImage = wf_img($channelScreenshot, $cameraLabel, 'width: 400px; height: 270px; object-fit: cover;');
                            $channelLink = wf_Link($channelUrl, $channelImage);
                            $result .= $channelLink;
                        }
                        $result .= wf_tag('div', true);
                    }
                }
                $result .= wf_tag('div', true);
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('No assigned cameras to show'), 'warning');
        }
        return($result);
    }

    /**
     * Returns all running livestreams real process PID-s array as pid=>processString
     * 
     * @return array
     */
    protected function getLiveStreamsPids() {
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
                        //is this really live stream process?
                        if (ispos($rawLine, $this->liveOptsSuffix) AND ispos($rawLine, self::STREAM_PLAYLIST)) {
                            $result[$eachPid] = $rawLine;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns running cameras live stream processes as cameraId=>realPid
     * 
     * @return array
     */
    public function getRunningStreams() {
        $result = array();
        if (!empty($this->allCamerasData)) {
            $liveStreamPids = $this->getLiveStreamsPids();
            if (!empty($liveStreamPids)) {
                foreach ($this->allCamerasData as $eachCameraId => $eachCameraData) {
                    foreach ($liveStreamPids as $eachPid => $eachProcess) {
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
     * Returns some channel human-readable comment
     * 
     * @param string $channelId
     * 
     * @return string
     */
    public function getCameraComment($channelId) {
        $result = '';
        if ($channelId) {
            $result .= $this->cameras->getCameraComment($channelId);
        }
        return($result);
    }

    /**
     * Allocates streams path, returns it if its writable
     * 
     * @return string/void on error
     */
    protected function allocateStreamPath($channelId) {
        $result = '';
        if (!file_exists($this->streamsPath)) {
            mkdir($this->streamsPath, 0777);
            chmod($this->streamsPath, 0777);
            log_register('LIVECAMS ALLOCATED `' . $this->streamsPath . '`');
        }

        if (file_exists($this->streamsPath)) {
            if (is_writable($this->streamsPath)) {
                $livePath = $this->streamsPath . $channelId;
                if (!file_exists($livePath)) {
                    mkdir($livePath, 0777);
                    chmod($livePath, 0777);
                    log_register('LIVECAMS ALLOCATED `' . $livePath . '`');
                }

                $result = $livePath . '/';
            }
        }
        return($result);
    }

    /**
     * Starts live stream capture
     * 
     * @return void
     */
    public function runStream($cameraId) {
        $this->stardust->setProcess(self::PID_PREFIX . $cameraId);
        if ($this->stardust->notRunning()) {
            $this->stardust->start();
            if (isset($this->allCamerasData[$cameraId])) {
                $cameraData = $this->allCamerasData[$cameraId];
                if ($cameraData['CAMERA']['active']) {
                    $allRunningStreams = $this->getRunningStreams();
                    if (!isset($allRunningStreams[$cameraId])) {
                        if (zb_PingICMP($cameraData['CAMERA']['ip'])) {
                            $channelId = $cameraData['CAMERA']['channel'];
                            $streamPath = $this->allocateStreamPath($channelId);
                            if ($cameraData['TEMPLATE']['PROTO'] == 'rtsp') {
                                //set stream as alive
                                $streamDog = new StreamDog();
                                $streamDog->keepAlive($cameraId);
                                //run live stream capture
                                $authString = $cameraData['CAMERA']['login'] . ':' . $cameraData['CAMERA']['password'] . '@';
                                $streamType = $cameraData['TEMPLATE']['MAIN_STREAM']; //TODO: may be configurable in future?
                                $streamUrl = $cameraData['CAMERA']['ip'] . ':' . $cameraData['TEMPLATE']['RTSP_PORT'] . $streamType;
                                $captureFullUrl = "'rtsp://" . $authString . $streamUrl . "'";
                                $liveCommand = $this->ffmpgPath . ' ' . $this->liveOptsPrefix . ' ' . $captureFullUrl . ' ' . $this->liveOptsSuffix . ' ' . self::STREAM_PLAYLIST;
                                $fullCommand = 'cd ' . $streamPath . ' && ' . $liveCommand;
                                shell_exec($fullCommand);
                            }
                        } else {
                            log_register('LIVECAMS NOTSTARTED [' . $cameraId . '] CAMERA NOT ACCESSIBLE');
                        }
                    }
                } else {
                    log_register('LIVECAMS NOTSTARTED [' . $cameraId . '] CAMERA DISABLED');
                }
            }

            $this->stardust->stop();
        }
    }

    /**
     * Destroys live stream. Returns true if stream was alive.
     * 
     * @return bool
     */
    public function stopStream($cameraId) {
        $result = false;
        $cameraId = ubRouting::filters($cameraId, 'int');
        $allRunningStreams = $this->getRunningStreams();
        //is camera live stream running?
        if (isset($allRunningStreams[$cameraId])) {
            //killing stream process
            $streamPid = $allRunningStreams[$cameraId];
            $command = $this->binPaths['SUDO'] . ' ' . $this->binPaths['KILL'] . ' -9 ' . $streamPid;
            shell_exec($command);
            //livestream location cleanup
            if (isset($this->allCamerasData[$cameraId])) {
                $cameraData = $this->allCamerasData[$cameraId];
                $channelId = $cameraData['CAMERA']['channel'];
                $streamPath = $this->allocateStreamPath($channelId);
                if (file_exists($streamPath)) {
                    $playListPath = $streamPath . self::STREAM_PLAYLIST;
                    if (file_exists($playListPath)) {
                        unlink($playListPath);
                    }
                }
            }
            $result = true;
        }
        return($result);
    }

    /**
     * Returns live stream full URL
     * 
     * @param string $channelId
     * 
     * @return string
     */
    public function getStreamUrl($channelId) {
        $result = '';
        $streamPath = $this->allocateStreamPath($channelId);
        if ($streamPath) {
            $cameraId = $this->cameras->getCameraIdByChannel($channelId);
            if ($cameraId) {
                $this->stardust->setProcess(self::PID_PREFIX . $cameraId);
                if ($this->stardust->notRunning()) {
                    $this->stardust->runBackgroundProcess(self::WRAPPER . ' "liveswarm&cameraid=' . $cameraId . '"', 1);
                }

                $fullStreamUrl = $streamPath . self::STREAM_PLAYLIST;
                if (file_exists($fullStreamUrl)) {
                    $result = $fullStreamUrl;
                } else {
                    $retries = 5;
                    for ($i = 0; $i < $retries; $i++) {
                        sleep(1);
                        if (file_exists($fullStreamUrl)) {
                            $result = $fullStreamUrl;
                            break;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Renders camera keep alive container
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    protected function renderKeepAliveCallback($cameraId) {
        $result = '';
        $streamDog = new StreamDog();
        $timeout = 10000; // in ms
        $keepAliveLink = self::URL_ME . '&' . StreamDog::ROUTE_KEEPALIVE . '=' . $cameraId;
        //preventing stream destroy before first callback
        $streamDog->keepAlive($cameraId);
        //appending periodic requests code
        $result .= $streamDog->getKeepAliveCallback($keepAliveLink, $timeout);
        return($result);
    }

    /**
     * Returns channel live stream preview
     * 
     * @param string $channelId
     * 
     * @return string
     */
    public function renderLive($channelId) {
        $result = '';
        $cameraId = $this->cameras->getCameraIdByChannel($channelId);
        $cameraControls = wf_BackLink(self::URL_ME);

        if ($cameraId) {
            $cameraData = $this->allCamerasData[$cameraId];
            if ($cameraData['CAMERA']['active']) {
                $streamUrl = $this->getStreamUrl($channelId);
                if ($streamUrl) {
                    //seems live stream now live
                    $playerId = 'liveplayer_' . $channelId;
                    $player = new Player($this->playerWidth, true);
                    $result .= $player->renderLivePlayer($streamUrl, $playerId);
                    $result .= $this->renderKeepAliveCallback($cameraId);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Oh no') . ': ' . __('No such live stream'), 'error');
                }
            } else {

                $result .= $this->messages->getStyledMessage(__('Oh no') . ': ' . __('Camera disabled now'), 'error');
            }

            if (cfr('CAMERAS')) {
                $cameraControls .= wf_Link(Cameras::URL_ME . '&' . Cameras::ROUTE_EDIT . '=' . $cameraData['CAMERA']['id'], wf_img('skins/icon_camera_small.png') . ' ' . __('Camera'), false, 'ubButton');
            }

            if (cfr('ARCHIVE')) {
                $cameraControls .= wf_Link(Archive::URL_ME . '&' . Archive::ROUTE_VIEW . '=' . $cameraData['CAMERA']['channel'], wf_img('skins/icon_archive_small.png') . ' ' . __('Video from camera'), false, 'ubButton');
            }

            if (cfr('EXPORT')) {
                $cameraControls .= wf_Link(Export::URL_ME . '&' . Export::ROUTE_CHANNEL . '=' . $cameraData['CAMERA']['channel'], wf_img('skins/icon_export.png') . ' ' . __('Save record'), false, 'ubButton');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Oh no') . ': ' . __('No such camera'), 'error');
        }

        $result .= wf_delimiter();
        $result .= $cameraControls;
        return($result);
    }

    /**
     * Returns pseudo-live stream HLS playlist
     * 
     * @param string $channelId
     * 
     * @return string
     */
    public function getPseudoStream($channelId) {
        $result = '';
        $streamUrl = $this->getStreamUrl($channelId);
        if (!empty($streamUrl)) {
            $cameraId = $this->cameras->getCameraIdByChannel($channelId);
            $playlistBody = file_get_contents($streamUrl);
            $prefix = Storages::PATH_HOWL . self::STREAMS_SUBDIR . $channelId . '/';
            if (!empty($playlistBody)) {
                $playlistBody = explodeRows($playlistBody);
                foreach ($playlistBody as $io => $eachLine) {
                    if (!empty($eachLine)) {
                        if (!ispos($eachLine, '#')) {
                            $eachLine = $prefix . $eachLine;
                        }
                        $result .= $eachLine . PHP_EOL;
                    }
                }
                //keeping stream alive
                if ($cameraId) {
                    $streamDog = new StreamDog();
                    $streamDog->keepAlive($cameraId);
                }
            }
        }
        return($result);
    }
}
