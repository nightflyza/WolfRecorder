<?php

/**
 * Draft and dirty implementation of chunks object detection
 */
class NeuralObjSearch {

    /**
     * binpaths config as key=>value
     * 
     * @var array
     */
    protected $binpaths = array();

    /**
     * Base WR web URL
     * 
     * @var string
     */
    protected $baseUrl = '';

    /**
     * Stardust process manager instance
     * 
     * @var object
     */
    protected $stardust = '';

    /**
     * Cameras object placeholder
     * 
     * @var object
     */
    protected $cameras = '';

    /**
     * Storages object placeholder
     * 
     * @var object
     */
    protected $storages = '';

    /**
     * Neural detector engine placeholder
     * 
     * @var object
     */
    protected $detector = '';

    /**
     * System message helper
     * 
     * @var object
     */
    protected $messages = '';

    /**
     * Contains default screenshot options
     *
     * @var string
     */
    protected $screenshotOpts = '-loglevel error -frames:v 1 -q:v 15';

    /**
     * Contains ffmpeg binary path
     *
     * @var string
     */
    protected $ffmpgPath = '';

    /**
     * Default chunk time offset to screenshot
     *
     * @var string
     */
    protected $timeOffset = '00:00:01';
    //some props that may be configurable in future
    protected $confidenceThreshold = 42;
    protected $timeLimit = 1200;
    protected $cachePath = 'howl/nd';

    //some predefined stuff here
    const AJAX_CONTAINER = 'neuralobjectssearchcontainer';
    const ROUTE_CHAN_DETECT = 'neuralobjects';
    const ROUTE_DATE = 'searchondate';
    const DETECTOR_PID = 'NEURAL_DETECTOR';

    public function __construct() {
        $this->setOptions();
        $this->initMessages();
        $this->initStardust();
    }

    /**
     * Sets required instance properties
     * 
     * @return void
     */
    protected function setOptions() {
        global $ubillingConfig;
        $this->binPaths = $ubillingConfig->getBinpaths();
        $this->ffmpgPath = $this->binPaths['FFMPG_PATH'];
        $webPath = pathinfo($_SERVER['REQUEST_URI']);
        $webPath = $webPath['dirname'];
        $proto = 'http://';
        $this->baseUrl = $proto . $_SERVER['HTTP_HOST'] . $webPath . '/';
    }

    /**
     * Renders AJAX container
     * 
     * @return string
     */
    public function renderContainer() {
        $result = '';
        $result .= wf_AjaxLoader();
        $result .= wf_AjaxContainer(self::AJAX_CONTAINER, '', '');
        $result .= wf_CleanDiv();
        return($result);
    }

    /**
     * Inits message helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits process manager
     * 
     * @return void
     */
    protected function initStardust() {
        $this->stardust = new StarDust(self::DETECTOR_PID);
    }

    /**
     * Inits camera instance
     * 
     * @return void
     */
    protected function initCameras() {
        $this->cameras = new Cameras();
    }

    /**
     * Inits storages instance
     * 
     * @return void
     */
    protected function initStorages() {
        $this->storages = new Storages();
    }

    /**
     * Inits neural detector engine API
     * 
     * @return void
     */
    protected function initDetector() {
        $this->detector = new NREngine();
    }

    /**
     * Renders object detection results for some channel
     * 
     * @param string $channelId
     * @param array $detectionsArray
     * 
     * @return string
     */
    protected function renderDetections($channelId, $detectionsArray) {
        $result = '';
        if (!empty($detectionsArray)) {
            $delimiter = ', ';
            $chanUrl = Archive::URL_ME . '&' . Archive::ROUTE_VIEW . '=' . $channelId;
            $cells = wf_TableCell(__('Time'));
            $cells .= wf_TableCell(__('Objects'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($detectionsArray as $chunkTimeStamp => $detectedObjects) {
                $chunkTime = date("H:i", $chunkTimeStamp);
                $chunkDate = date("Y-m-d", $chunkTimeStamp);
                $objectsList = '';
                if (!empty($detectedObjects)) {
                    foreach ($detectedObjects as $io => $each) {
                        $objectsList .= __($each) . $delimiter;
                    }
                }
                $objectsList = rtrim($objectsList, $delimiter);
                $viewUrl = $chanUrl . '&' . Archive::ROUTE_SHOWDATE . '=' . $chunkDate . '&' . Archive::ROUTE_TIMESEGMENT . '=' . $chunkTime;

                $cells = wf_TableCell(wf_Link($viewUrl, $chunkTime, false, 'camlink'));
                $cells .= wf_TableCell($objectsList);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'resp-table');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
            $result .= wf_delimiter();
        }
        return($result);
    }

    /**
     * Performs fast all chunks object detection for some channel
     * 
     * @param string $channelId
     * @param string $date
     * 
     * @return void
     */
    public function renderObjectDetector($channelId, $date = '') {
        $result = '';
        if ($this->stardust->notRunning()) {
            $this->stardust->start();
            set_time_limit($this->timeLimit);
            $this->initDetector();
            if ($this->detector->isAlive()) {
                $this->initCameras();
                $this->initStorages();

                $cameraId = $this->cameras->getCameraIdByChannel($channelId);
                if ($cameraId) {
                    $dateFrom = $date;
                    $allCamerasData = $this->cameras->getAllCamerasFullData();
                    $cameraData = $allCamerasData[$cameraId]['CAMERA'];
                    $cameraStorageData = $allCamerasData[$cameraId]['STORAGE'];

                    $dateFromTs = strtotime($dateFrom . ' 00:00:00');
                    $dateToTs = strtotime($dateFrom . ' 23:59:59');
                    $minuteBetweenNow = strtotime('-1 minute', time());

                    $storagePath = $cameraStorageData['path'];
                    $storagePathLastChar = substr($storagePath, 0, -1);

                    $howlChunkPath = Storages::PATH_HOWL . '/';

                    $chunksList = $this->storages->getChannelChunks($cameraData['storageid'], $cameraData['channel']);

                    $filteredChunks = array();
                    $detectionsTmp = array();

                    if (!empty($chunksList)) {
                        //cache alloc 
                        if (!file_exists($this->cachePath)) {
                            mkdir($this->cachePath, 0777);
                        }
                        if (!file_exists($this->cachePath . '/' . $channelId)) {
                            mkdir($this->cachePath . '/' . $channelId, 0777);
                        }

                        //per chunk hell
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

                        if (!empty($filteredChunks)) {
                            foreach ($filteredChunks as $eachChunk => $eachChunkPath) {
                                $fsCacheDir = $this->cachePath . '/' . $channelId . '/';
                                $fsCacheName = $fsCacheDir . $eachChunk . '.ndobj';
                                $chunkScreenName = $fsCacheDir . $eachChunk . '.jpg';
                                if (!file_exists($fsCacheName)) {
                                    //print($eachChunkPath . '<br>');
                                    if (!file_exists($chunkScreenName)) {
                                        $command = $this->ffmpgPath . ' -ss ' . $this->timeOffset . ' -i ' . $chunkPath . ' ' . $this->screenshotOpts . ' ' . $chunkScreenName;
                                        print($command.'<br>');
                                      //  shell_exec($command);
                                    }
//                                    $chunkDetections = $this->detector->detectObjects($eachUrl);
//                                    if (isset($chunkDetections['detections'])) {
//                                        if (!empty($chunkDetections['detections'])) {
//                                            foreach ($chunkDetections as $io => $each) {
//                                                $chunkDatetime = date("Y-m-d H:i:s", $eachChunk);
//                                                $objectsList = array();
//                                                foreach ($chunkDetections['detections'] as $io => $each) {
//                                                    if ($each['confidence'] >= $this->confidenceThreshold) {
//                                                        $objectsList [] = $each['label'];
//                                                    }
//                                                }
//
//                                                if (!empty($objectsList)) {
//                                                    $detectionsTmp[$eachChunk] = $objectsList;
//                                                }
//                                                //filling chunk cache
//                                                file_put_contents($fsCacheName, json_encode($objectsList));
//                                            }
//                                        }
//                                    }
                                } else {
                                    //reading from cache
                                    $rawObjList = file_get_contents($fsCacheName);
                                    $rawObjList = json_decode($rawObjList, true);
                                    if (!empty($rawObjList)) {
                                        $detectionsTmp[$eachChunk] = $rawObjList;
                                    }
                                }
                            }
                        }

                        if (!empty($detectionsTmp)) {
                            $result .= $this->renderDetections($channelId, $detectionsTmp);
                        }
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Camera') . ' ' . __('not exists'), 'error');
                    $result .= wf_delimiter();
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Neural object recognition service now is offline'), 'error');
                $result .= wf_delimiter();
            }
            $this->stardust->stop();
        } else {
            $result .= $this->messages->getStyledMessage(__('Neural network is busy at this moment'), 'warning');
            $result .= wf_delimiter();
        }

        die($result);
    }
}
