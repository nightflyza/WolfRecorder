<?php

/**
 * VOD archive implementation
 */
class Archive {

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
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains chunk time in seconds
     */
    protected $chunkTime = 60;

    /**
     * Contains player width by default
     *
     * @var string
     */
    protected $playerWidth = '70%';

    /**
     * other predefined stuff like routes
     */
    const PLAYLIST_MASK = '_playlist.txt';
    const URL_ME = '?module=archive';
    const ROUTE_VIEW = 'viewchannel';
    const ROUTE_SHOWDATE = 'renderdatearchive';
    const ROUTE_TIMESEGMENT = 'tseg';

    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->setOptions();
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
        $this->chunkTime = $this->altCfg['RECORDER_CHUNK_TIME'];
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
     * Renders available cameras list
     * 
     * @return string
     */
    public function renderCamerasList() {
        $result = '';
        $allStotagesData = $this->storages->getAllStoragesData();
        if (!empty($allStotagesData)) {
            if (!empty($this->allCamerasData)) {
                $cells = '';
                if (cfr('CAMERAS')) {
                    $cells .= wf_TableCell(__('ID'));
                    $cells .= wf_TableCell(__('IP'));
                }
                $cells .= wf_TableCell(__('Description'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($this->allCamerasData as $io => $each) {
                    $eachCamId = $each['CAMERA']['id'];
                    $eachCamIp = $each['CAMERA']['ip'];
                    $eachCamDesc = $each['CAMERA']['comment'];
                    $eachCamChannel = $each['CAMERA']['channel'];
                    $cells = '';
                    if (cfr('CAMERAS')) {
                        $cells .= wf_TableCell($eachCamId);
                        $cells .= wf_TableCell($eachCamIp, '', '', 'sorttable_customkey="' . ip2int($eachCamIp) . '"');
                    }
                    $eachCamUrl = self::URL_ME . '&' . self::ROUTE_VIEW . '=' . $eachCamChannel;
                    $cells .= wf_TableCell(wf_Link($eachCamUrl, $eachCamDesc, false, 'camlink'));
                    $actLinks = wf_Link($eachCamUrl, wf_img('skins/icon_play_small.png', __('View')));
                    $cells .= wf_TableCell($actLinks);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
            } else {
                $result .= $this->messages->getStyledMessage(__('Cameras') . ': ' . __('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Storages') . ': ' . __('Nothing found'), 'warning');
        }
        return($result);
    }

    /**
     * Renders howl player for previously generated playlist
     * 
     * @param string $playlistPath - full playlist path
     * @param bool $autoPlay - start playback right now?
     * @param string $playerId - must be equal to channel name to access playlist in DOM
     * 
     * @return string
     */
    protected function renderArchivePlayer($playlistPath, $autoPlay = false, $playerId = '') {
       $plStart = '';
       
        if (!ubRouting::checkGet(self::ROUTE_SHOWDATE)) {
            $fewMinsAgo = strtotime("-5 minute", time());
            $fewMinsAgo = date("H:i", $fewMinsAgo);
            $plStart = ', plstart:"s_' . $fewMinsAgo . '"';
        }

        //explict time segment setup
        if (ubRouting::checkGet(self::ROUTE_TIMESEGMENT)) {
            $plStart = ', plstart:"s_' . ubRouting::get(self::ROUTE_TIMESEGMENT, 'mres') . '"';
        }

        $player = new Player($this->playerWidth, $autoPlay);
        $result = $player->renderPlaylistPlayer($playlistPath, $plStart, $playerId);

        return($result);
    }

    /**
     * Allocates array with full timeline as hh:mm=>0
     * 
     * @return array
     */
    public function allocDayTimeline() {
        $result = array();
        for ($h = 0; $h <= 23; $h++) {
            for ($m = 0; $m < 60; $m++) {
                $hLabel = ($h > 9) ? $h : '0' . $h;
                $mLabel = ($m > 9) ? $m : '0' . $m;
                $timeLabel = $hLabel . ':' . $mLabel;
                $result[$timeLabel] = 0;
            }
        }
        return($result);
    }

    /**
     * Renders recordings availability due some day of month
     * 
     * @return string
     */
    protected function renderDayRecordsAvailTimeline($chunksList, $date) {
        $result = '';
        if (!empty($chunksList)) {
            $dayMinAlloc = $this->allocDayTimeline();
            $chunksByDay = 0;
            $curDate = curdate();
            $fewMinAgo = strtotime("-5 minute", time());
            $fewMinLater = strtotime("+1 minute", time());
            foreach ($chunksList as $timeStamp => $eachChunk) {
                $dayOfMonth = date("Y-m-d", $timeStamp);
                if ($dayOfMonth == $date) {
                    $timeOfDay = date("H:i", $timeStamp);
                    if (isset($dayMinAlloc[$timeOfDay])) {
                        $dayMinAlloc[$timeOfDay] = 1;
                        $chunksByDay++;
                    }
                }
            }


            //any records here?
            if ($chunksByDay) {
                if ($chunksByDay > 3) {
                    $barWidth = 0.064;
                    $barStyle = 'width:' . $barWidth . '%;';
                    $result = wf_tag('div', false, '', 'style = "width:' . $this->playerWidth . ';"');
                    foreach ($dayMinAlloc as $eachMin => $recAvail) {
                        $recAvailBar = ($recAvail) ? 'skins/rec_avail.png' : 'skins/rec_unavail.png';
                        if ($curDate == $date) {
                            $eachMinTs = strtotime($date . ' ' . $eachMin . ':00');
                            if (zb_isTimeStampBetween($fewMinAgo, $fewMinLater, $eachMinTs)) {
                                $recAvailBar = 'skins/rec_now.png';
                            }
                        }
                        $recAvailTitle = ($recAvail) ? $eachMin : $eachMin . ' - ' . __('No record');
                        $timeBarLabel = wf_img($recAvailBar, $recAvailTitle, $barStyle);
                        if ($recAvail) {
                            $timeSeg = self::URL_ME . '&' . self::ROUTE_VIEW . '=' . ubRouting::get(self::ROUTE_VIEW) . '&' . self::ROUTE_SHOWDATE . '=' . $date . '&' . self::ROUTE_TIMESEGMENT . '=' . $eachMin;
                            $result .= trim(wf_Link($timeSeg, $timeBarLabel));
                        } else {
                            $result .= $timeBarLabel;
                        }
                    }
                    $result .= wf_tag('div', true);
                }
                $result .= wf_delimiter(0);
            }
        }

        return($result);
    }

    /**
     * Renders basic timeline for some chunks list
     * 
     * @param string $channelId
     * @param array $chunksList
     * 
     * @return string
     */
    protected function renderDaysTimeline($channelId, $chunksList) {
        $result = '';
        $channelId = ubRouting::filters($channelId, 'mres');
        $dayPointer = ubRouting::checkGet(self::ROUTE_SHOWDATE) ? ubRouting::get(self::ROUTE_SHOWDATE) : curdate();
        if (!empty($chunksList)) {
            $datesTmp = array();
            foreach ($chunksList as $timeStamp => $chunkName) {
                $chunkDate = date("Y-m-d", $timeStamp);
                if (!isset($datesTmp[$chunkDate])) {
                    $datesTmp[$chunkDate] = 1;
                } else {
                    $datesTmp[$chunkDate] ++;
                }
            }

            if (!empty($datesTmp)) {
                $result .= $this->renderDayRecordsAvailTimeline($chunksList, $dayPointer);

                $chunkTime = $this->altCfg['RECORDER_CHUNK_TIME'];
                foreach ($datesTmp as $eachDate => $chunksCount) {
                    $justDay = date("d", strtotime($eachDate));
                    $baseUrl = self::URL_ME . '&' . self::ROUTE_VIEW . '=' . $channelId . '&' . self::ROUTE_SHOWDATE . '=' . $eachDate;
                    $recordsTime = wr_formatTimeArchive($chunksCount * $chunkTime);
                    $buttonIcon = ($eachDate == $dayPointer) ? 'skins/icon_play_small.png' : 'skins/icon_calendar.gif';
                    $result .= wf_Link($baseUrl, wf_img($buttonIcon, $eachDate . ' - ' . $recordsTime) . ' ' . $justDay, false, 'ubButton') . ' ';
                }
                $result .= wf_CleanDiv();
            }
        }
        return($result);
    }

    /**
     * Saves playlist and returns its path in howl ready for player rendering
     * 
     * @param int $cameraId
     * @param string $dateFrom
     * @param string $dateTo
     * @param array $chunksList
     * 
     * @return string/void on error
     */
    protected function generateArchivePlaylist($cameraId, $dateFrom, $dateTo, $chunksList = array()) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCamerasData[$cameraId])) {
            $curDate = curdate();
            $dateFromTs = strtotime($dateFrom . ' 00:00:00');
            $dateToTs = strtotime($dateFrom . ' 23:59:59');
            $minuteBetweenNow = strtotime('-1 minute', time());
            $cameraData = $this->allCamerasData[$cameraId]['CAMERA'];
            $cameraStorageData = $this->allCamerasData[$cameraId]['STORAGE'];
            $storagePath = $cameraStorageData['path'];
            $storagePathLastChar = substr($storagePath, 0, -1);

            if ($storagePathLastChar != '/') {
                $storagePath = $storagePath . '/';
            }
            $howlChunkPath = Storages::PATH_HOWL . '/';
            if (empty($chunksList)) {
                $chunksList = $this->storages->getChannelChunks($cameraData['storageid'], $cameraData['channel']);
            }
            $filteredChunks = array();
            if (!empty($chunksList)) {
                foreach ($chunksList as $chunkTimeStamp => $chunkPath) {

                    if (zb_isTimeStampBetween($dateFromTs, $dateToTs, $chunkTimeStamp)) {
                        $howlChunkFullPath = str_replace($storagePath, $howlChunkPath, $chunkPath);
                        $howlChunkFullPath = str_replace('//', '/', $howlChunkFullPath);
                        //excluding last minute chunk - it may be unfinished now
                        if ($chunkTimeStamp < $minuteBetweenNow) {
                            $filteredChunks[$chunkTimeStamp] = $howlChunkFullPath;
                        }
                    }
                }
            }


//generating playlist
            if (!empty($filteredChunks)) {
                $playListPath = Storages::PATH_HOWL . $cameraData['channel'] . self::PLAYLIST_MASK;
                $segmentsCount = sizeof($filteredChunks);
                $playlistContent = '[' . PHP_EOL;
                $i = 0;
                foreach ($filteredChunks as $chunkTimeStamp => $chunkFile) {
                    $i++;
                    $chunkTitle = date("Y-m-d H:i:s", $chunkTimeStamp);
                    $segmentId = 's' . '_' . date("H:i", $chunkTimeStamp);
                    $playlistContent .= '{"title":"' . $chunkTitle . '","file":"' . $chunkFile . '","id":"' . $segmentId . '"}';
                    if ($i < $segmentsCount) {
                        $playlistContent .= ',';
                    }
                    $playlistContent .= PHP_EOL;
                }
                $playlistContent .= ']' . PHP_EOL;
                file_put_contents($playListPath, $playlistContent);
                $result = $playListPath;
            }
        }
        return($result);
    }

    /**
     * Renders basic archive lookup interface
     * 
     * @param string $channelId
     * 
     * @return string
     */
    public function renderLookup($channelId) {
        $result = '';
        $channelId = ubRouting::filters($channelId, 'mres');
        //camera ID lookup by channel
        $allCamerasChannels = $this->cameras->getAllCamerasChannels();
        $cameraId = (isset($allCamerasChannels[$channelId])) ? $allCamerasChannels[$channelId] : 0;

        if ($cameraId) {
            if (isset($this->allCamerasData[$cameraId])) {
                $cameraData = $this->allCamerasData[$cameraId]['CAMERA'];
                $showDate = (ubRouting::checkGet(self::ROUTE_SHOWDATE)) ? ubRouting::get(self::ROUTE_SHOWDATE, 'mres') : curdate();
                $chunksList = $this->storages->getChannelChunks($cameraData['storageid'], $cameraData['channel']);
                if (!empty($chunksList)) {
                    $archivePlayList = $this->generateArchivePlaylist($cameraId, $showDate, $showDate, $chunksList);
                    if ($archivePlayList) {
                        $result .= $this->renderArchivePlayer($archivePlayList, true, $cameraData['channel']);
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
                        $result .= wf_delimiter(0);
                    }
                    //some timeline here
                    $result .= $this->renderDaysTimeline($channelId, $chunksList);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Camera') . ' [' . $cameraId . '] ' . __('not exists'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Camera') . ' ' . __('with channel') . ' `' . $channelId . '` ' . __('not exists'), 'error');
        }
        $result .= wf_delimiter(1);
        $result .= wf_BackLink(self::URL_ME);
        if (cfr('CAMERAS')) {
            if ($cameraId) {
                $result .= wf_Link(Cameras::URL_ME . '&' . Cameras::ROUTE_EDIT . '=' . $cameraId, wf_img('skins/icon_camera_small.png') . ' ' . __('Camera'), false, 'ubButton');
            }
        }
        if (cfr('EXPORT')) {
            $result .= wf_Link(Export::URL_ME . '&' . Export::ROUTE_CHANNEL . '=' . $channelId, wf_img('skins/icon_export.png') . ' ' . __('Save record'), false, 'ubButton');
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

}
