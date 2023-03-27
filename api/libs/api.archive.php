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
    const PLAYLIST_MASK = '_arch.txt';
    const URL_ME = '?module=archive';
    const ROUTE_VIEW = 'viewcameraarchive';
    const ROUTE_SHOWDATE = 'renderdatearchive';

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
                $cells = wf_TableCell(__('ID'));
                $cells .= wf_TableCell(__('IP'));
                $cells .= wf_TableCell(__('Description'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($this->allCamerasData as $io => $each) {
                    $eachCamId = $each['CAMERA']['id'];
                    $eachCamIp = $each['CAMERA']['ip'];
                    $eachCamDesc = $each['CAMERA']['comment'];
                    $cells = wf_TableCell($eachCamId);
                    $cells .= wf_TableCell($eachCamIp);
                    $cells .= wf_TableCell($eachCamDesc);
                    $actLinks = wf_Link(self::URL_ME . '&' . self::ROUTE_VIEW . '=' . $eachCamId, web_icon_search(__('View')));
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
     * @param string $playlistPath
     * @param string $width
     * @param bool $autoPlay
     * 
     * @return string
     */
    protected function renderArchivePlayer($playlistPath, $width = '600px', $autoPlay = false) {
        $autoPlay = ($autoPlay) ? 'true' : 'false';
        $playerId = 'archplayer' . wf_InputId();
        $result = '';
        $result .= '<script src="modules/jsc/playerjs/w_playerjs.js"></script >
                     <div style="float:left; width:' . $width . '; margin:5px;">
                     <div id="' . $playerId . '" style="width:90%;"></div >
        	    <script >var player = new Playerjs({id:"' . $playerId . '", file:"' . $playlistPath . '", autoplay:' . $autoPlay . '});</script >
                   </div>
            ';
        $result .= wf_CleanDiv();
        return($result);
    }

    /**
     * Allocates array with full timeline as hh:mm=>0
     * 
     * @return array
     */
    protected function allocDayTimeline() {
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
                $barWidth = 0.064;
                $result = wf_tag('div', false, '', 'style="width:' . $this->playerWidth . ';"');
                foreach ($dayMinAlloc as $eachMin => $recAvail) {
                    $recAvailBar = ($recAvail) ? 'skins/rec_avail.png' : 'skins/rec_unavail.png';
                    $recAvailTitle = ($recAvail) ? $eachMin : $eachMin . ' - ' . __('No record');
                    $result .= wf_img($recAvailBar, $recAvailTitle, 'width:' . $barWidth . '%;');
                }
                $result .= wf_tag('div', true);
            }
        }

        return($result);
    }

    /**
     * Renders basic timeline for some chunks list
     * 
     * @param array $cameraId
     * @param array $chunksList
     * 
     * @return string
     */
    protected function renderDaysTimeline($cameraId, $chunksList) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
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
                $result .= wf_delimiter(0);
                $chunkTime = $this->altCfg['RECORDER_CHUNK_TIME'];
                foreach ($datesTmp as $eachDate => $chunksCount) {
                    $justDay = date("d", strtotime($eachDate));
                    $baseUrl = self::URL_ME . '&' . self::ROUTE_VIEW . '=' . $cameraId . '&' . self::ROUTE_SHOWDATE . '=' . $eachDate;
                    $recordsTime = wr_formatTimeArchive($chunksCount * $chunkTime);
                    $buttonIcon = ($eachDate == $dayPointer) ? 'skins/icon_camera_small.png' : 'skins/icon_calendar.gif';
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
     * 
     * @return string/void on error
     */
    protected function generateArchivePlaylist($cameraId, $dateFrom, $dateTo) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCamerasData[$cameraId])) {
            $cameraData = $this->allCamerasData[$cameraId]['CAMERA'];
            $cameraStorageData = $this->allCamerasData[$cameraId]['STORAGE'];
            $storagePath = $cameraStorageData['path'];
            $storagePathLastChar = substr($storagePath, 0, -1);

            if ($storagePathLastChar != '/') {
                $storagePath = $storagePath . '/';
            }
            $howlChunkPath = Storages::PATH_HOWL . '/';
            $chunksList = $this->storages->getChannelChunks($cameraData['storageid'], $cameraData['channel']);
            $filteredChunks = array();
            if (!empty($chunksList)) {
                foreach ($chunksList as $timeStamp => $chunkPath) {
                    $chunkDate = date("Y-m-d", $timeStamp);
                    if (zb_isDateBetween($dateFrom, $dateTo, $chunkDate)) {
                        $howlChunkFullPath = str_replace($storagePath, $howlChunkPath, $chunkPath);
                        $howlChunkFullPath = str_replace('//', '/', $howlChunkFullPath);
                        $filteredChunks[$timeStamp] = $howlChunkFullPath;
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
                    $playlistContent .= '{"title":"' . $chunkTitle . '","file":"' . $chunkFile . '","id":"s' . $i . '"}';
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
     * @param int $cameraId
     * 
     * @return string
     */
    public function renderLookup($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCamerasData[$cameraId])) {
            $cameraData = $this->allCamerasData[$cameraId]['CAMERA'];
            $showDate = (ubRouting::checkGet(self::ROUTE_SHOWDATE)) ? ubRouting::get(self::ROUTE_SHOWDATE, 'mres') : curdate();
            $chunksList = $this->storages->getChannelChunks($cameraData['storageid'], $cameraData['channel']);
            if (!empty($chunksList)) {
                $archivePlayList = $this->generateArchivePlaylist($cameraId, $showDate, $showDate);
                if ($archivePlayList) {
                    $result .= $this->renderArchivePlayer($archivePlayList, $this->playerWidth, true);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
                }
                //some timeline here
                $result .= $this->renderDaysTimeline($cameraId, $chunksList);
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Camera') . ' [' . $cameraId . '] ' . __('not exists'), 'error');
        }
        $result .= wf_delimiter(1);
        $result .= wf_BackLink(self::URL_ME);
        return($result);
    }

}
