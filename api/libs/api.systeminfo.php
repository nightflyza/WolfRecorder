<?php

/**
 * Basic system health report
 */
class SystemInfo {

    /**
     * Contains alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System hardware info instance placeholder
     *
     * @var object
     */
    protected $hwInfo = '';

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
        $this->initHwInfo();
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
     * Inits system hw info instance
     * 
     * @return void
     */
    protected function initHwInfo() {
        $this->hwInfo = new SystemHwInfo();
    }

    /**
     * Renders current system load average stats
     * 
     * @return string
     */
    public function renderSysLoad() {
        $size=280;
        $container=$size+20;
        $result = '';
        $result .= wf_tag('h3') . __('System load') . wf_tag('h3', true);
        $laOpts = '
             max: 100,
             min: 0,
             width: ' . $size . ', height: ' . $size . ',
             greenFrom: 0, greenTo: 40,
             yellowFrom:40, yellowTo: 70,
             redFrom: 70, redTo: 100,
             minorTicks: 5
                      ';

        $result .= wf_renderGauge($this->hwInfo->getloadAvgPercent(), ' ' . __('on average'), '%', $laOpts, $container);
        $result .= wf_renderGauge($this->hwInfo->getLoadPercent1(), '1' . ' ' . __('minutes'), '%', $laOpts, $container);
        $result .= wf_renderGauge($this->hwInfo->getLoadPercent5(), '5' . ' ' . __('minutes'), '%', $laOpts, $container);
        $result .= wf_renderGauge($this->hwInfo->getLoadPercent15(), '15' . ' ' . __('minutes'), '%', $laOpts, $container);
        $result .= wf_CleanDiv();

        return ($result);
    }

    /**
     * Renders some free space data about free disk space
     * 
     * @return string
     */
    public function renderDisksCapacity() {
        $size=280;
        $usedSpaceArr = array();
        $mountPoints = array();
        $mountPointNames = array();
        $availableStorages = $this->storages->getAllStoragesData();
        $allStorageNames = $this->storages->getAllStorageNamesLocalized();

        //root fs
        $mountPoints[0]='/';
        $mountPointNames['/'] = __('System');

        if (!empty($availableStorages)) {
            foreach ($availableStorages as $storageId => $each) {
                $mountPoints[$each['id']] = $each['path'];
                $mountPointNames[$each['path']] =  $allStorageNames[$each['id']];
            }
        }
        
        $this->hwInfo->setMountPoints($mountPoints);
        $usedSpaceArr = $this->hwInfo->getAllDiskStats();

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
             width: ' . $size . ', height: ' . $size . ',
             greenFrom:' . $greenFrom . ', greenTo: ' . $greenTo . ',
             yellowFrom:' . $yellowFrom . ', yellowTo: ' . $yellowTo . ',
             redFrom:' . $redFrom . ', redTo:' . $redTo . ',
             minorTicks: 5
                      ';

        if (!empty($usedSpaceArr)) {
            foreach ($usedSpaceArr as $mountPoint => $spaceStats) {
                $freeLabel = wr_convertSize($spaceStats['free']);
                $totalLabel = wr_convertSize($spaceStats['total']);
                $partitionLabel = $mountPointNames[$mountPoint] . ' - ' . $freeLabel . ' ' . __('of') . ' ' . $totalLabel . ' ' . __('Free');
                $result .= wf_renderGauge(round($spaceStats['usedpercent']), $partitionLabel, '%', $opts, ($size+20));
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('No storages available'), 'warning');
        }
        $result .= wf_CleanDiv();
        return ($result);
    }

    /**
     * Renders current instance serial as info-box
     * 
     * @return string
     */
    public function renderSerialInfo() {
        $result = '';
        $instanceSerial = wr_SerialGet();
        if (!empty($instanceSerial)) {
            $rawRelease = file_get_contents('RELEASE');
            $infoLabel = __('WolfRecorder') . ' ' . $rawRelease . ', ';
            $infoLabel .= __('This system serial number') . ': ' . wf_tag('b') . $instanceSerial . wf_tag('b', true);
            $result .= $this->messages->getStyledMessage(wf_tag('center') . $infoLabel . wf_tag('center', true), 'info');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }

        $cpuName = $this->hwInfo->getCpuName();
        $cpuCores = $this->hwInfo->getCpuCores();
        $memTotal = $this->hwInfo->getMemTotal();
        $uptime = $this->hwInfo->getUptime();
        $osLabel = $this->hwInfo->getOs() . ' ' . $this->hwInfo->getOsRelease() . ', ';
        $phpLabel = __('PHP') . ': ' . $this->hwInfo->getPhpVersion() . ', ';
        $memLabel = wr_convertSize($memTotal) . ' ' . __('RAM') . '.';
        $uptimeLabel = __('Uptime') . ': ' . zb_formatTime($uptime);
        $sysLabel = __('CPU') . ': ' . $cpuName . ', ' . $cpuCores . ' ' . __('Cores') . ', ' . $memLabel;
        $sysLabel .= ' ' . $osLabel . ' ' . $phpLabel . ' ' . $uptimeLabel;
        $result .= $this->messages->getStyledMessage(wf_tag('center') . $sysLabel . wf_tag('center', true), 'success');

        $result .= wf_delimiter(0);
        return ($result);
    }
}
