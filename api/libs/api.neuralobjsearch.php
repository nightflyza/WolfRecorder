<?php

class NeuralObjSearch {

    protected $baseUrl = '';
    protected $stardust = '';
    protected $cameras = '';
    protected $storages = '';
    protected $detector = '';
    protected $messages = '';
    protected $confidenceThreshold = 40;

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

    protected function setOptions() {
        $webPath = pathinfo($_SERVER['REQUEST_URI']);
        $webPath = $webPath['dirname'];
        $proto = 'http://';
        $this->baseUrl = $proto . $_SERVER['HTTP_HOST'] . $webPath . '/';
    }

    public function renderContainer() {
        $result = '';
        $result .= wf_AjaxLoader();
        $result .= wf_AjaxContainer(self::AJAX_CONTAINER, '', '');
        $result .= wf_CleanDiv();
        return($result);
    }

    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    protected function initStardust() {
        $this->stardust = new StarDust(self::DETECTOR_PID);
    }

    protected function initCameras() {
        $this->cameras = new Cameras();
    }

    protected function initStorages() {
        $this->storages = new Storages();
    }

    protected function initDetector() {
        $this->detector = new NREngine();
    }

    protected function renderDetections($channelId, $detectionsArray) {
        $result = '';
        if (!empty($detectionsArray)) {
            $chanUrl = Archive::URL_ME . '&' . Archive::ROUTE_VIEW . '=' . $channelId;
            $cells = wf_TableCell(__('Time'));
            $cells .= wf_TableCell(__('Objects'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($detectionsArray as $chunkTimeStamp => $detectedObjects) {
                $chunkTime = date("H:i", $chunkTimeStamp);
                $chunkDate = date("Y-m-d", $chunkTimeStamp);
                $objectsList = implode(', ', $detectedObjects);
                $viewUrl = $chanUrl . '&' . Archive::ROUTE_SHOWDATE . '=' . $chunkDate . '&' . Archive::ROUTE_TIMESEGMENT . '=' . $chunkTime;

                $cells = wf_TableCell(wf_Link($viewUrl, $chunkTime));
                $cells .= wf_TableCell($objectsList);
                $rows .= wf_TableRow($cells);
            }

            $result .= wf_TableBody($rows, '100%', 0, 'resp-table');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
            $result .= wf_delimiter();
        }
        return($result);
    }

    public function renderObjectDetector($channelId, $date) {
        $result = '';
        if ($this->stardust->notRunning()) {
            $this->stardust->start();
            set_time_limit(0);
            $this->initDetector();
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
                    foreach ($chunksList as $chunkTimeStamp => $chunkPath) {

                        if (zb_isTimeStampBetween($dateFromTs, $dateToTs, $chunkTimeStamp)) {
                            $howlChunkFullPath = str_replace($storagePath, $howlChunkPath, $chunkPath);
                            $howlChunkFullPath = str_replace('//', '/', $howlChunkFullPath);
                            //excluding last minute chunk - it may be unfinished now
                            if ($chunkTimeStamp < $minuteBetweenNow) {
                                $filteredChunks[$chunkTimeStamp] = $this->baseUrl . $howlChunkFullPath;
                            }
                        }
                    }

                    if (!empty($filteredChunks)) {
                        foreach ($filteredChunks as $eachChunk => $eachUrl) {
                            $chunkDetections = $this->detector->detectObjects($eachUrl);
                            if (isset($chunkDetections['detections'])) {
                                if (!empty($chunkDetections['detections'])) {
                                    foreach ($chunkDetections as $io => $each) {
                                        $chunkDatetime = date("Y-m-d H:i:s", $eachChunk);
                                        $objectsList = array();
                                        foreach ($chunkDetections['detections'] as $io => $each) {
                                            if ($each['confidence'] >= $this->confidenceThreshold) {
                                                $objectsList [] = __($each['label']);
                                            }
                                        }

                                        if (!empty($objectsList)) {
                                            $detectionsTmp[$eachChunk] = $objectsList;
                                        }
                                    }
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
            $this->stardust->stop();
        } else {
            $result .= $this->messages->getStyledMessage(__('Neural network is busy at this moment'), 'warning');
            $result .= wf_delimiter();
        }

        die($result);
    }
}
