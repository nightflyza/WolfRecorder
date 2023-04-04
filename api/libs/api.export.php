<?php

/**
 * Archive records export implementation
 */
class Export {

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
     * other predefined stuff like routes
     */
    const EXPORTLIST_MASK = '_exportlist.txt';
    const URL_ME = '?module=export';
    const ROUTE_CHANNEL = 'exportchannel';
    const PROUTE_DATE_FROM = 'datefrom';
    const PROUTE_DATE_TO = 'dateto';
    const PROUTE_TIME_FROM = 'timefrom';
    const PROUTE_TIME_TO = 'timeto';

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
                }
                $cells .= wf_TableCell(__('IP'));
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
                    }
                    $cells .= wf_TableCell($eachCamIp);
                    $cells .= wf_TableCell($eachCamDesc);
                    $actLinks = wf_Link(self::URL_ME . '&' . self::ROUTE_CHANNEL . '=' . $eachCamChannel, web_icon_search(__('View')));
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

}
