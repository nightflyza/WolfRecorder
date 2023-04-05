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
     * Archive instance placeholder.
     *
     * @var object
     */
    protected $archive = '';

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
     * Contains current instance user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * other predefined stuff like routes
     */
    const EXPORTLIST_MASK = '_el.txt';
    const URL_ME = '?module=export';
    const URL_RECORDS = '?module=records';
    const ROUTE_CHANNEL = 'exportchannel';
    const ROUTE_SHOWDATE = 'exportdatearchive';
    const ROUTE_DELETE = 'delrec';
    const PROUTE_DATE_EXPORT = 'dateexport';
    const PROUTE_TIME_FROM = 'timefrom';
    const PROUTE_TIME_TO = 'timeto';
    const PATH_RECORDS = 'howl/recdl/';
    const PID_EXPORT = 'EXPORT_';
    const RECORDS_EXT = '.mp4';

    public function __construct() {
        $this->setLogin();
        $this->initMessages();
        $this->loadConfigs();
        $this->setOptions();
        $this->initStorages();
        $this->initCameras();
        $this->initArchive();
    }

    /**
     * Sets current instance login
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
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
     * Inits archive into protected prop
     * 
     * @return void
     */
    protected function initArchive() {
        $this->archive = new Archive();
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
                    $actLinks = wf_Link(self::URL_ME . '&' . self::ROUTE_CHANNEL . '=' . $eachCamChannel, web_icon_download());
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
     * Renders recordings availability due some day of month
     * 
     * @return string
     */
    protected function renderDayRecordsAvailability($chunksList, $date) {
        $result = '';
        if (!empty($chunksList)) {
            $dayMinAlloc = $this->archive->allocDayTimeline();
            $chunksByDay = 0;
            $curDate = curdate();
            $fewMinAgo = date("H:i", strtotime("-5 minute", time()));
            $fewMinLater = date("H:i", strtotime("+1 minute", time()));
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
                    $result = wf_tag('div', false, '', 'style = "width:100%;"');
                    foreach ($dayMinAlloc as $eachMin => $recAvail) {
                        $recAvailBar = ($recAvail) ? 'skins/rec_avail.png' : 'skins/rec_unavail.png';
                        if ($curDate == $date) {
                            if (zb_isTimeBetween($fewMinAgo, $fewMinLater, $eachMin)) {
                                $recAvailBar = 'skins/rec_now.png';
                            }
                        }
                        $recAvailTitle = ($recAvail) ? $eachMin : $eachMin . ' - ' . __('No record');
                        $timeBarLabel = wf_img($recAvailBar, $recAvailTitle, $barStyle);
                        $result .= $timeBarLabel;
                    }
                    $result .= wf_tag('div', true);
                }
                $result .= wf_delimiter(0);
            }
        }

        return($result);
    }

    /**
     * Renders export form with timeline for some chunks list
     * 
     * @param string $channelId
     * @param array $chunksList
     * 
     * @return string
     */
    protected function renderExportForm($channelId, $chunksList) {
        $result = '';
        $channelId = ubRouting::filters($channelId, 'mres');
        $dayPointer = ubRouting::checkPost(self::PROUTE_DATE_EXPORT) ? ubRouting::post(self::PROUTE_DATE_EXPORT) : curdate();
        if (!empty($chunksList)) {
            $datesTmp = array();
            foreach ($chunksList as $timeStamp => $chunkName) {
                $chunkDate = date("Y-m-d", $timeStamp);
                $datesTmp[$chunkDate] = $chunkDate;
            }
            if (!empty($datesTmp)) {
                $inputs = wf_Selector(self::PROUTE_DATE_EXPORT, $datesTmp, __('Date'), $dayPointer, false) . ' ';
                $inputs .= wf_TimePickerPreset(self::PROUTE_TIME_FROM, ubRouting::post(self::PROUTE_TIME_FROM), __('from'), false) . ' ';
                $inputs .= wf_TimePickerPreset(self::PROUTE_TIME_TO, ubRouting::post(self::PROUTE_TIME_TO), __('to'), false) . ' ';
                $inputs .= wf_Submit(__('Export'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
                //here some timeline for selected day
                $result .= $this->renderDayRecordsAvailability($chunksList, $dayPointer);
                $result .= wf_CleanDiv();
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
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
    public function renderExportLookup($channelId) {
        $result = '';
        $channelId = ubRouting::filters($channelId, 'mres');
        //camera ID lookup by channel
        $allCamerasChannels = $this->cameras->getAllCamerasChannels();
        $cameraId = (isset($allCamerasChannels[$channelId])) ? $allCamerasChannels[$channelId] : 0;

        if ($cameraId) {
            if (isset($this->allCamerasData[$cameraId])) {
                $cameraData = $this->allCamerasData[$cameraId]['CAMERA'];
                $showDate = (ubRouting::checkGet(self::ROUTE_SHOWDATE)) ? ubRouting::get(self::ROUTE_SHOWDATE, 'mres') : curdate();
                //any chunks here?
                $chunksList = $this->storages->getChannelChunks($cameraData['storageid'], $cameraData['channel']);
                if (!empty($chunksList)) {
                    $result .= $this->renderExportForm($channelId, $chunksList);
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
        if (cfr('ARCHIVE')) {
            $result .= wf_Link(Archive::URL_ME . '&' . Archive::ROUTE_VIEW . '=' . $channelId, wf_img('skins/icon_archive_small.png') . ' ' . __('Archive'), false, 'ubButton');
        }
        return($result);
    }

    /**
     * Prepares per-user recordings space
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    protected function prepareRecordingsDir($userLogin = '') {
        $result = '';
        if (empty($userLogin)) {
            //using current user`s instance
            $userLogin = $this->myLogin;
        }
        if (!empty($userLogin)) {
            $fullUserPath = self::PATH_RECORDS . $userLogin;
            //base recordings path
            if (!file_exists(self::PATH_RECORDS)) {
                //creating base path
                mkdir(self::PATH_RECORDS, 0777);
                chmod(self::PATH_RECORDS, 0777);
            }

            if (!file_exists($fullUserPath)) {
                //and per-user path
                mkdir($fullUserPath, 0777);
                chmod($fullUserPath, 0777);
            }

            if (file_exists($fullUserPath)) {
                $result = $fullUserPath . '/'; //with ending slash
            }
        }
        return($result);
    }

    /**
     * Returns space used by user recordings
     * 
     * @param string $recordsDir
     * 
     * @return int
     */
    protected function getUserUsedSpace($recordsDir) {
        $result = 0;
        if (!empty($recordsDir)) {
            $allRecords = rcms_scandir($recordsDir);
            if (!empty($allRecords)) {
                foreach ($allRecords as $io => $eachRecord) {
                    $result += filesize($recordsDir . $eachRecord);
                }
            }
        }
        return($result);
    }

    /**
     * Returns count of users registered in system
     * 
     * @return int
     */
    protected function getUserCount() {
        $result = 0;
        $allUsers = rcms_scandir(USERS_PATH);
        if (!empty($allUsers)) {
            $result = sizeof($allUsers);
        }
        return($result);
    }

    /**
     * Returns count bytes count allowed to each user to store his records
     * 
     * @return int
     */
    protected function getUserMaxSpace() {
        $result = 0;
        $storageTotalSpace = disk_total_space('/');
        $storageFreeSpace = disk_free_space('/');
        $usedStorageSpace = $storageTotalSpace - $storageFreeSpace;
        $maxUsagePercent = 100 - ($this->altCfg['STORAGE_RESERVED_SPACE'] / 2); // half of reserved space
        $maxUsageSpace = zb_Percent($storageTotalSpace, $maxUsagePercent);
        $mustBeFree = $storageTotalSpace - $maxUsageSpace;
        $usersCount = $this->getUserCount();
        if ($usersCount > 0) {
            $result = $mustBeFree / $usersCount;
        }
        return($result);
    }

    /**
     * Performs export of some chunks list of some channel into selected directory
     * 
     * @param array $chunksList
     * @param string $channelId
     * @param string $directory
     * @param string $userLogin
     * 
     * @return void/string
     */
    protected function exportChunksList($chunksList, $channelId, $directory, $userLogin) {
        $result = '';
        $exportProcess = new StarDust(self::PID_EXPORT . $channelId);
        if ($exportProcess->notRunning()) {
            $exportProcess->start();
            $allChannels = $this->cameras->getAllCamerasChannels();
            $cameraId = $allChannels[$channelId];
            log_register('EXPORT STARTED CAMERA [' . $cameraId . '] CHANNEL `' . $channelId . '`');
            if (!empty($chunksList)) {
                $firstTs = 0;
                $lastTs = 0;
                $exportListData = '';
                $exportListPath = Storages::PATH_HOWL . $channelId . '_' . zb_rand_string(8) . self::EXPORTLIST_MASK;
                //building concat list here
                foreach ($chunksList as $eachTimeStamp => $eachChunk) {
                    if (file_exists($eachChunk)) {
                        if (!$firstTs) {
                            $firstTs = $eachTimeStamp;
                        }
                        $lastTs = $eachTimeStamp;
                        $exportListData .= "file '" . $eachChunk . "'" . PHP_EOL;
                    }
                }

                //saving export list
                file_put_contents($exportListPath, $exportListData);
                //record file name
                $dateFmt = "Y-m-d-H-i-s";
                $recordFileName = date($dateFmt, $firstTs) . '_' . date($dateFmt, $lastTs) . '_' . $channelId . self::RECORDS_EXT;
                $fullRecordFilePath = $directory . $recordFileName;
                if (!file_exists($fullRecordFilePath)) {
                    $command = $this->ffmpgPath . ' -loglevel error -f concat -safe 0 -i ' . $exportListPath . ' -c copy ' . $fullRecordFilePath;
                    die($command);
                    shell_exec($command);
                } else {
                    log_register('EXPORT SKIPPED CAMERA [' . $cameraId . '] CHANNEL `' . $channelId . '` ALREADY EXISTS');
                }
                //cleanup export list
                // unlink($exportListPath);
            } else {
                $result .= __('Something went wrong');
            }
            $exportProcess->stop();
            log_register('EXPORT FINISHED CAMERA [' . $cameraId . '] CHANNEL `' . $channelId . '`');
        } else {
            $result .= __('Export process already running');
        }
        return($result);
    }

    /**
     * Performs export of some channels records into single file
     * 
     * @param string $channelId
     * @param string $date
     * @param string $timeFrom
     * @param string $timeTo
     * 
     * @return void/string on error
     */
    public function runExport($channelId, $date, $timeFrom, $timeTo) {
        $result = '';
        $userRecordingsDir = $this->prepareRecordingsDir(); //anyway we need this
        $channelId = ubRouting::filters($channelId, 'mres');
        $date = ubRouting::filters($date, 'mres');
        $timeFrom = ubRouting::filters($timeFrom, 'mres');
        $timeTo = ubRouting::filters($timeTo, 'mres');

        $fullDateFrom = strtotime($date . $timeFrom . ':00');
        $fullDateTo = strtotime($date . $timeTo . ':59');

        $allCameraChannels = $this->cameras->getAllCamerasChannels();
        //TODO: here must be some per user ACL checks
        if (isset($allCameraChannels[$channelId])) {
            $cameraId = $allCameraChannels[$channelId];
            if (isset($this->allCamerasData[$cameraId])) {
                $cameraData = $this->allCamerasData[$cameraId];
                $storageId = $cameraData['STORAGE']['id'];
                $allChannelChunks = $this->storages->getChannelChunks($storageId, $channelId);
                if (!empty($allCameraChannels)) {
                    $chunksInRange = $this->storages->filterChunksTimeRange($allChannelChunks, $fullDateFrom, $fullDateTo);
                    if (!empty($chunksInRange)) {
                        $chunksSize = $this->storages->getChunksSize($chunksInRange); //total chunks size
                        $usedSpace = $this->getUserUsedSpace($userRecordingsDir); //space used by user
                        $maxSpace = $this->getUserMaxSpace(); //max of reserved space for each user
                        $usageForecast = $usedSpace + $chunksSize; //how much space will be with current export?
                        //checking is some of user space left?
                        if ($usageForecast <= $maxSpace) {
                            //TODO: replace this with just schedule creation
                            $result .= $this->exportChunksList($chunksInRange, $channelId, $userRecordingsDir, $this->myLogin);
                        } else {
                            $result .= __('There is not enough space reserved for exporting your records');
                        }
                    } else {
                        $result .= __('No records in archive for this time range');
                    }
                } else {
                    $result .= __('Nothing to export');
                }
            } else {
                $result .= __('Camera') . ' [' . $cameraId . '] ' . __('not exists');
            }
        } else {
            $result .= __('Camera') . ' ' . __('with channel') . ' `' . $channelId . '` ' . __('not exists');
        }

        return($result);
    }

    /**
     * Parses exported file name
     * 
     * @param string $fileName
     * 
     * @return array
     */
    public function parseRecordFileName($fileName) {
        $result = array();
        $cleanName = str_replace(self::RECORDS_EXT, '', $fileName);
        $explodedName = explode('_', $cleanName);
        if (sizeof($explodedName) == 3) {
            $rawFrom = explode('-', $explodedName[0]);
            $from = $rawFrom[0] . '-' . $rawFrom[1] . '-' . $rawFrom[2] . ' ' . $rawFrom[3] . ':' . $rawFrom[4] . ':' . $rawFrom[5];
            $rawTo = explode('-', $explodedName[1]);
            $to = $rawTo[0] . '-' . $rawTo[1] . '-' . $rawTo[2] . ' ' . $rawTo[3] . ':' . $rawTo[4] . ':' . $rawTo[5];
            $result['from'] = $from;
            $result['to'] = $to;
            $result['channel'] = $explodedName[2];
        }
        return($result);
    }

    /**
     * Renders recording file deletion dialog
     * 
     * @param string $fileName
     * 
     * @return string
     */
    protected function renderRecDelDialog($fileName) {
        $result = '';
        $currentModule = ubRouting::get('module');
        if (!empty($currentModule)) {
            if ($currentModule == 'export') {
                $channelId = ubRouting::get(self::ROUTE_CHANNEL);
                $deleteUrl = self::URL_ME . '&' . self::ROUTE_CHANNEL . '=' . $channelId . '&' . self::ROUTE_DELETE . '=' . $fileName;
                $cancelUrl = self::URL_ME . '&' . self::ROUTE_CHANNEL . '=' . $channelId;
                $result .= wf_ConfirmDialog($deleteUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', $cancelUrl, __('Delete') . ' ' . __('Recording') . '?');
            }

            if ($currentModule == 'records') {
                $deleteUrl = self::URL_RECORDS . '&' . self::ROUTE_DELETE . '=' . $fileName;
                $cancelUrl = self::URL_RECORDS;
                $result .= wf_ConfirmDialog($deleteUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', $cancelUrl, __('Delete') . ' ' . __('Recording') . '?');
            }
        }
        return($result);
    }

    /**
     * Returns list of available records
     * 
     * @param string $channelId
     * 
     * @return string
     */
    public function renderAvailableRecords($channelId = '') {
        $result = '';
        $userRecordingsDir = $this->prepareRecordingsDir();
        $recordsExtFilter = '*' . self::RECORDS_EXT;
        $allRecords = rcms_scandir($userRecordingsDir, $recordsExtFilter);
        //channel filter applied?
        if ($channelId) {
            if (!empty($allRecords)) {
                foreach ($allRecords as $io => $each) {
                    if (!ispos($each, $channelId)) {
                        unset($allRecords[$io]);
                    }
                }
            }
        }
        if (!empty($allRecords)) {
            $cells = wf_TableCell(__('Time') . ' ' . __('from'));
            $cells .= wf_TableCell(__('Time') . ' ' . __('to'));
            $cells .= wf_TableCell(__('Camera'));
            $cells .= wf_TableCell(__('Size'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($allRecords as $io => $eachFile) {
                $fileNameParts = $this->parseRecordFileName($eachFile);
                $cells = wf_TableCell($fileNameParts['from']);
                $cells .= wf_TableCell($fileNameParts['to']);
                $cells .= wf_TableCell($this->cameras->getCameraComment($fileNameParts['channel']));
                $recordSize = filesize($userRecordingsDir . $eachFile);
                $recordSizeLabel = wr_convertSize($recordSize);
                $cells .= wf_TableCell($recordSizeLabel);
                $fileUrl = $userRecordingsDir . $eachFile;
                $actLinks = $this->renderRecDelDialog($eachFile);
                $actLinks .= wf_Link($fileUrl, web_icon_download());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'resp-table sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        $maxUserSpace = $this->getUserMaxSpace();
        $usedSpaceByMe = $this->getUserUsedSpace($userRecordingsDir);
        $spaceFree = $maxUserSpace - $usedSpaceByMe;
        $spaceLabel = ($spaceFree > 0) ? wr_convertSize($spaceFree) : __('Exhausted') . ' :(';
        $result .= $this->messages->getStyledMessage(__('Free space for exporting your records') . ': ' . $spaceLabel, 'info');
        return($result);
    }

    /**
     * Deletes existing recording file
     * 
     * @param string $fileName
     * @param string $userLogin
     * 
     * @return void/string
     */
    public function deleteRecording($fileName, $userLogin = '') {
        $result = '';
        $fileName = ubRouting::filters($fileName, 'mres');
        if (empty($userLogin)) {
            $userLogin = $this->myLogin;
        }
        if (!empty($fileName)) {
            $userRecordingsDir = $this->prepareRecordingsDir($userLogin);
            if (!empty($userRecordingsDir)) {
                if (file_exists($userRecordingsDir . $fileName)) {
                    if (is_writable($userRecordingsDir . $fileName)) {
                        unlink($userRecordingsDir . $fileName);
                        log_register('EXPORT DELETE `' . $fileName . '`');
                    } else {
                        $result .= __('Recording') . ' ' . __('is not writable');
                        log_register('EXPORT DELETE FAIL `' . $fileName . '` NOT WRITABLE');
                    }
                } else {
                    $result .= __('Recording') . ' ' . __('not exists');
                    log_register('EXPORT DELETE FAIL `' . $fileName . '` NOT EXISTS');
                }
            }
        } else {
            $result .= __('Recording') . ' ' . __('is empty');
            log_register('EXPORT DELETE FAIL FILENAME EMPTY');
        }
        return($result);
    }

}
