<?php

class SystemInfo {

    /**
     * Contains alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains binpaths config as key=>value
     *
     * @var array
     */
    protected $binPaths = array();

    /**
     * Storages instance placeholder.
     *
     * @var object
     */
    protected $storages = '';

    /**
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->initStorages();
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
     * Loads all required configs
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->binPaths = $ubillingConfig->getBinpaths();
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
     * Renders current system load average stats
     * 
     * @return string
     */
    public function renderLA() {
        $loadAvg = sys_getloadavg();
        $laGauges = '';
        $laGauges .= wf_tag('h3') . __('Load Average') . wf_tag('h3', true);
        $laOpts = '
             max: 10,
             min: 0,
             width: ' . 280 . ', height: ' . 280 . ',
             greenFrom: 0, greenTo: 2,
             yellowFrom:2, yellowTo: 5,
             redFrom: 5, redTo: 10,
             minorTicks: 5
                      ';
        $laGauges .= wf_renderGauge(round($loadAvg[0], 2), '1' . ' ' . __('minutes'), 'LA', $laOpts, 300);
        $laGauges .= wf_renderGauge(round($loadAvg[1], 2), '5' . ' ' . __('minutes'), 'LA', $laOpts, 300);
        $laGauges .= wf_renderGauge(round($loadAvg[2], 2), '15' . ' ' . __('minutes'), 'LA', $laOpts, 300);
        $laGauges .= wf_CleanDiv();
        return($laGauges);
    }

    /**
     * Renders some free space data about free disk space
     * 
     * @return string
     */
    public function renderDisksCapacity() {
        $usedSpaceArr = array();
        $mountPoints = array();
        $availableStorages = $this->storages->getAllStoragesData();

        $allStorageNames = $this->storages->getAllStorageNamesLocalized();
        if (!empty($availableStorages)) {
            foreach ($availableStorages as $storageId => $each) {
                $mountPoints[$each['id']] = $each['path'];
            }
        }

        if (!empty($mountPoints)) {
            foreach ($mountPoints as $storageId => $each) {
                $totalSpace = disk_total_space($each);
                $freeSpace = disk_free_space($each);
                $usedSpaceArr[$each]['percent'] = zb_PercentValue($totalSpace, ($totalSpace - $freeSpace));
                $usedSpaceArr[$each]['total'] = $totalSpace;
                $usedSpaceArr[$each]['free'] = $freeSpace;
                $usedSpaceArr[$each]['name'] = $allStorageNames[$storageId];
            }
        }

        $reservedPercent = $this->altCfg['STORAGE_RESERVED_SPACE'];
        $maxCapacity = 100;
        $greenFrom = 0;
        $greenTo = $maxCapacity - $reservedPercent;
        $yellowFrom = $greenTo;
        $yellowTo = ($maxCapacity - $reservedPercent) + ($reservedPercent / 2);
        $redFrom = $yellowTo;
        $redTo = $maxCapacity;


        $result = '';
        $result .= wf_tag('h3') . __('Storages capacity') . wf_tag('h3', true);
        $opts = '
             max: 100,
             min: 0,
             width: ' . 280 . ', height: ' . 280 . ',
             greenFrom:' . $greenFrom . ', greenTo: ' . $greenTo . ',
             yellowFrom:' . $yellowFrom . ', yellowTo: ' . $yellowTo . ',
             redFrom:' . $redFrom . ', redTo:' . $redTo . ',
             minorTicks: 5
                      ';

        if (!empty($usedSpaceArr)) {
            foreach ($usedSpaceArr as $mountPoint => $spaceStats) {
                $partitionLabel = $spaceStats['name'] . ' - ' . wr_convertSize($spaceStats['free']) . ' ' . __('Free');
                $result .= wf_renderGauge(round($spaceStats['percent']), $partitionLabel, '%', $opts, 300);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('No storages available'), 'warning');
        }
        $result .= wf_CleanDiv();
        return($result);
    }

}
