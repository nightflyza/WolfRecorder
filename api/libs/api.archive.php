<?php

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
     * some creepy params here
     */
    protected $chunkTime = 60;

    /**
     * other predefined stuff like routes
     */
    const URL_ME = '?module=archive';
    const ROUTE_VIEW = 'viewcameraarchive';

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
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($this->allCamerasData as $io => $each) {
                    $eachCamId = $each['CAMERA']['id'];
                    $eachCamIp = $each['CAMERA']['ip'];
                    $cells = wf_TableCell($eachCamId);
                    $cells .= wf_TableCell($eachCamIp);
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
            $chunksList = $this->storages->getChannelChunks($cameraData['storageid'], $cameraData['channel']);
            //TODO: finish it later
            debarr($chunksList);
        } else {
            $result .= $this->messages->getStyledMessage(__('Camera') . ' [' . $cameraId . '] ' . __('not exists'), 'error');
        }
        $result .= wf_delimiter(0);
        $result .= wf_BackLink(self::URL_ME);
        return($result);
    }

}
