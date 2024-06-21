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
     * Contains scheduler database abstraction layer
     *
     * @var object
     */
    protected $scheduleDb = '';

    /**
     * ACL instance placeholder
     *
     * @var object
     */
    protected $acl = '';

    /**
     * other predefined stuff like routes
     */
    const EXPORTLIST_MASK = '_el.txt';
    const URL_ME = '?module=export';
    const URL_RECORDS = '?module=records';
    const ROUTE_CHANNEL = 'exportchannel';
    const ROUTE_SHOWDATE = 'exportdatearchive';
    const ROUTE_DELETE = 'delrec';
    const ROUTE_PREVIEW = 'previewrecord';
    const ROUTE_SCHED_OK = 'scheduledsuccess';
    const ROUTE_BACK_EXPORT = 'chanback';
    const PROUTE_DATE_EXPORT = 'dateexport';
    const PROUTE_TIME_FROM = 'timefrom';
    const PROUTE_TIME_TO = 'timeto';
    const PATH_RECORDS = 'howl/recdl/';
    const PID_EXPORT = 'EXPORT_';
    const PID_SCHEDULE = 'EXPORTSCHEDULE';
    const RECORDS_EXT = '.mp4';
    const TABLE_SCHED = 'schedule';

    public function __construct() {
        $this->setLogin();
        $this->initMessages();
        $this->loadConfigs();
        $this->setOptions();
        $this->initStorages();
        $this->initCameras();
        $this->initArchive();
        $this->initScheduleDb();
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
     * Inits schedule database abstraction laye
     * 
     * @return void
     */
    protected function initScheduleDb() {
        $this->scheduleDb = new NyanORM(self::TABLE_SCHED);
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
     * Renders available cameras list
     * 
     * @return string
     */
    public function renderCamerasList() {
        $result = '';
        $this->initAcl();
        if ($this->acl->haveCamsAssigned()) {
            $allStotagesData = $this->storages->getAllStoragesData();
            if (!empty($allStotagesData)) {
                if (!empty($this->allCamerasData)) {
                    $screenshots = new ChanShots();
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
                        if ($this->acl->isMyCamera($eachCamId)) {
                            $eachCamIp = $each['CAMERA']['ip'];
                            $eachCamDesc = $each['CAMERA']['comment'];
                            $eachCamChannel = $each['CAMERA']['channel'];
                            $cells = '';
                            if (cfr('CAMERAS')) {
                                $cells .= wf_TableCell($eachCamId);
                                $cells .= wf_TableCell($eachCamIp);
                            }
                            $eachCamUrl = self::URL_ME . '&' . self::ROUTE_CHANNEL . '=' . $eachCamChannel;
                            $camPreview = '';
                            $chanShot = $screenshots->getChannelScreenShot($eachCamChannel);
                            if (empty($chanShot)) {
                                $chanShot = $screenshots::ERR_NOSIG;
                            } else {
                                $chanshotValid = $screenshots->isChannelScreenshotValid($chanShot);
                                if (!$chanshotValid) {
                                    $chanShot = $screenshots::ERR_CORRUPT;
                                }
                            }
                            if (!$each['CAMERA']['active']) {
                                $chanShot = $screenshots::ERR_DISABLD;
                            }
                            $camPreview = $screenshots->renderListBox($eachCamChannel, $chanShot);
                            $cells .= wf_TableCell(wf_Link($eachCamUrl, $camPreview . $eachCamDesc, false, 'camlink'));
                            $actLinks = wf_Link($eachCamUrl, wf_img('skins/icon_export.png', __('Save records')));
                            $cells .= wf_TableCell($actLinks);
                            $rows .= wf_TableRow($cells, 'row5');
                        }
                    }
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Cameras') . ': ' . __('Nothing to show'), 'warning');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Storages') . ': ' . __('Nothing found'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('No assigned cameras to show'), 'warning');
        }
        return ($result);
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
                    $barWidth = 0.069444444;
                    $barStyle = 'width:' . $barWidth . '%;';
                    $result = wf_tag('div', false, '', 'style = "width:100%;"');
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
                        $result .= $timeBarLabel;
                    }
                    $result .= wf_tag('div', true);
                }
                $result .= wf_delimiter(0);
            }
        }

        return ($result);
    }

    /**
     * Returns custom inline datepicker
     * 
     * @param string $name
     * @param array $datesAvail
     * @param string $selected
     * 
     * @return string
     */
    protected function wf_InlineDatePicker($name, $datesAvail, $selected = '') {
        $result = '';
        $inputId = wf_InputId();
        $divId = 'inline' . $inputId;
        $result .= wf_HiddenInput($name, $selected, $inputId);
        $result .= wf_tag('div', false, '', 'id="' . $divId . '"') . wf_tag('div', true);
        $jsCode = wf_tag('script');
        $curlang = curlang();

        $locale = "monthNamesShort: ['" . rcms_date_localise('Jan') . "','" . rcms_date_localise('Feb') . "','" . rcms_date_localise('Mar') . "','" . rcms_date_localise('Apr') . "','" . rcms_date_localise('Ma') . "','" . rcms_date_localise('Jun') . "','" . rcms_date_localise('Jul') . "','" . rcms_date_localise('Aug') . "','" . rcms_date_localise('Sep') . "','" . rcms_date_localise('Oct') . "','" . rcms_date_localise('Nov') . "','" . rcms_date_localise('Dec') . "'],";
        $locale .= "dayNamesMin: ['" . rcms_date_localise('Sun') . "','" . rcms_date_localise('Mon') . "','" . rcms_date_localise('Tue') . "','" . rcms_date_localise('Wed') . "','" . rcms_date_localise('Thu') . "','" . rcms_date_localise('Fri') . "','" . rcms_date_localise('Sat') . "'],";
        $locale .= "prevText: '" . __('Previous') . "',";
        $locale .= "nextText: '" . __('Next') . "',";

        $daysEnable = '';
        if (!empty($datesAvail)) {
            $allowedDatesJs = '        var enableDays = [';
            foreach ($datesAvail as $io => $each) {
                $allowedDatesJs .= "'" . $each . "',";
            }
            $allowedDatesJs = substr($allowedDatesJs, 1, -1);
            $allowedDatesJs .= ']';
            $daysEnable .= $allowedDatesJs;
            $daysEnable .= " 
                function enableAllTheseDays(date) {
                var fDate = $.datepicker.formatDate('yy-mm-dd', date);
                var result = [false, \"\"];
                $.each(enableDays, function(k, d) {
                  if (fDate === d) {
                    result = [true, \"row1\"];
                  }
                });
                return result;
              }

           ";
        }

        $jsCode .= $daysEnable;
        $jsCode .= " $('#" . $divId . "').datepicker({
                        inline: true,
                        altField: '#" . $inputId . "',
                        changeMonth: true,
                        yearRange: \"-2:+0\",
                        changeYear: true,
                        dateFormat: 'yy-mm-dd',
                        firstDay: 1,
                        beforeShowDay: enableAllTheseDays,
                        " . $locale . "
                    }    
                    ); 


                    $('#" . $inputId . "').change(function(){
                        $('#" . $divId . "').datepicker('setDate', $(this).val());
                    });
                    
";
        $jsCode .= wf_tag('script', true);

        $result .= $jsCode;
        return ($result);
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
                //TODO: render latest channel screenshot here
                $chanShots = new ChanShots();
                $latestScreenShot = $chanShots->getChannelScreenShot($channelId);
                $grid = '';
                if ($latestScreenShot) {
                    $grid .= wf_tag('div', false, '', 'style="float:left; margin: 5px;"');
                    $grid .= wf_img_sized($latestScreenShot, '', '', '', 'float:left; height:240px;');
                    $grid .= wf_tag('div', true);
                }

                $grid .= wf_tag('div', false, '', 'style="float:left; margin: 5px;"');
                $grid .= $this->wf_InlineDatePicker(self::PROUTE_DATE_EXPORT, $datesTmp, $dayPointer);
                $grid .= wf_tag('div', true);

                $inputs = $grid;
                $inputs .= wf_CleanDiv();
                $inputs .= wf_TextInput(self::PROUTE_TIME_FROM, '', ubRouting::post(self::PROUTE_TIME_FROM), false, 5, '', self::PROUTE_TIME_FROM, self::PROUTE_TIME_FROM, 'style="display:none;"') . ' ';
                $inputs .= wf_TextInput(self::PROUTE_TIME_TO, '', ubRouting::post(self::PROUTE_TIME_TO), false, 5, '', self::PROUTE_TIME_TO, self::PROUTE_TIME_TO, 'style="display:none;"') . ' ';
                $sliderCode = file_get_contents('modules/jsc/exportSlider.js');
                $inputs .= wf_delimiter();
                //time range selection slider
                $inputs .= $sliderCode;
                $inputs .= wf_delimiter();
                //here some timeline for selected day to indicate records availability
                $inputs .= $this->renderDayRecordsAvailability($chunksList, $dayPointer);
                $inputs .= wf_Submit(__('Save record'));
                $result .= wf_Form('', 'POST', $inputs, '');


                $result .= wf_CleanDiv();
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        }
        return ($result);
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
        if (cfr('LIVECAMS')) {
            $result .= wf_Link(LiveCams::URL_ME . '&' . LiveCams::ROUTE_VIEW . '=' . $channelId, wf_img('skins/icon_live_small.png') . ' ' . __('Live'), false, 'ubButton');
        }
        if (cfr('ARCHIVE')) {
            $result .= wf_Link(Archive::URL_ME . '&' . Archive::ROUTE_VIEW . '=' . $channelId, wf_img('skins/icon_archive_small.png') . ' ' . __('Video from camera'), false, 'ubButton');
        }
        return ($result);
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
        return ($result);
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
        return ($result);
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
        return ($result);
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
        if (isset($this->altCfg['EXPORTS_RESERVED_SPACE'])) {
            $maxUsagePercent = 100 - ($this->altCfg['EXPORTS_RESERVED_SPACE']); // explict value
        } else {
            $maxUsagePercent = 100 - ($this->altCfg['STORAGE_RESERVED_SPACE'] / 2); // half of reserved space
        }
        $maxUsageSpace = zb_Percent($storageTotalSpace, $maxUsagePercent);
        $mustBeFree = $storageTotalSpace - $maxUsageSpace;
        $usersCount = $this->getUserCount();
        if ($usersCount > 0) {
            $result = $mustBeFree / $usersCount;
        }
        return ($result);
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
                $recordFileName = date($dateFmt, $firstTs) . '_' . date($dateFmt, $lastTs) . '_' . $cameraId . self::RECORDS_EXT;
                $fullRecordFilePath = $directory . $recordFileName;
                if (!file_exists($fullRecordFilePath)) {
                    $command = $this->ffmpgPath . ' -loglevel error -f concat -safe 0 -i ' . $exportListPath . ' -c copy ' . $fullRecordFilePath;
                    shell_exec($command);
                } else {
                    log_register('EXPORT SKIPPED CAMERA [' . $cameraId . '] CHANNEL `' . $channelId . '` ALREADY EXISTS');
                }
                //cleanup export list
                unlink($exportListPath);
            } else {
                $result .= __('Something went wrong');
            }
            $exportProcess->stop();
            log_register('EXPORT FINISHED CAMERA [' . $cameraId . '] CHANNEL `' . $channelId . '`');
        } else {
            $result .= __('Export process already running');
        }
        return ($result);
    }

    /**
     * Schedules future export operation
     * 
     * @param string $userLogin
     * @param string $channelId
     * @param string $dateFrom
     * @param string $dateTo
     * @param int $sizeForecast
     * 
     * @return void/string
     */
    public function scheduleExportTask($userLogin, $channelId, $dateFrom, $dateTo, $sizeForecast) {
        $result = '';
        $userLoginF = ubRouting::filters($userLogin, 'mres');
        $dateF = curdatetime();
        $channelIdF = ubRouting::filters($channelId, 'mres');
        $dateFromF = ubRouting::filters($dateFrom, 'mres');
        $dateToF = ubRouting::filters($dateTo, 'mres');
        $sizeForecastF = ubRouting::filters($sizeForecast, 'int');
        $allChannels = $this->cameras->getAllCamerasChannels();
        $cameraId = $allChannels[$channelId];
        if ($userLoginF and $channelId and $dateFrom and $dateTo and $cameraId) {
            $this->scheduleDb->data('date', $dateF);
            $this->scheduleDb->data('user', $userLogin);
            $this->scheduleDb->data('channel', $channelIdF);
            $this->scheduleDb->data('datetimefrom', $dateFromF);
            $this->scheduleDb->data('datetimeto', $dateToF);
            $this->scheduleDb->data('sizeforecast', $sizeForecastF);
            $this->scheduleDb->data('done', 0);
            $this->scheduleDb->create();
            log_register('EXPORT SCHEDULED CAMERA [' . $cameraId . '] CHANNEL `' . $channelId . '`');
        } else {
            $result .= __('Something went wrong');
        }
        return ($result);
    }

    /**
     * Performs processin of all scheduled exports tasks
     * 
     * @return void
     */
    public function scheduleRun() {
        $this->scheduleDb->where('done', '=', 0);
        $allScheduledTasks = $this->scheduleDb->getAll();
        if (!empty($allScheduledTasks)) {
            $allCameraChannels = $this->cameras->getAllCamerasChannels();
            foreach ($allScheduledTasks as $io => $each) {
                $userLogin = $each['user'];
                $userRecordingsDir = $this->prepareRecordingsDir($userLogin);
                $channelId = $each['channel'];
                $cameraId = $allCameraChannels[$channelId];
                $cameraData = $this->allCamerasData[$cameraId];
                $storageId = $cameraData['STORAGE']['id'];
                $allChannelChunks = $this->storages->getChannelChunks($storageId, $channelId);
                $dateTimeFromTs = strtotime($each['datetimefrom']);
                $dateTimeToTs = strtotime($each['datetimeto']);
                $chunksInRange = $this->storages->filterChunksTimeRange($allChannelChunks, $dateTimeFromTs, $dateTimeToTs);
                if ($chunksInRange and $userRecordingsDir) {
                    $this->exportChunksList($chunksInRange, $channelId, $userRecordingsDir, $userLogin);
                }
                //mark task as done
                $this->scheduleDb->where('id', '=', $each['id']);
                $this->scheduleDb->data('done', 1);
                $this->scheduleDb->data('finishdate', curdatetime());
                $this->scheduleDb->save();
            }
        }
    }

    /**
     * Returns expected scheduled records export size
     * 
     * @param string $userLogin
     * 
     * @return int
     */
    protected function scheduleGetForecastSize($userLogin) {
        $result = 0;
        $userLogin = ubRouting::filters($userLogin, 'mres');
        $this->scheduleDb->where('user', '=', $userLogin);
        $this->scheduleDb->where('done', '=', 0);
        $rawResult = $this->scheduleDb->getAll();
        if (!empty($rawResult)) {
            foreach ($rawResult as $io => $each) {
                $result += $each['sizeforecast'];
            }
        }

        return ($result);
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
    public function requestExport($channelId, $date, $timeFrom, $timeTo) {
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
                    if ($fullDateFrom < $fullDateTo) {
                        $chunksInRange = $this->storages->filterChunksTimeRange($allChannelChunks, $fullDateFrom, $fullDateTo);
                        if (!empty($chunksInRange)) {
                            $chunksSize = $this->storages->getChunksSize($chunksInRange); //total chunks size
                            $usedSpace = $this->getUserUsedSpace($userRecordingsDir); //space used by user
                            $maxSpace = $this->getUserMaxSpace(); //max of reserved space for each user
                            $scheduleForecast = $this->scheduleGetForecastSize($this->myLogin); //already scheduled tasks forecast
                            $usageForecast = $usedSpace + $chunksSize + $scheduleForecast; //how much space will be with current export?
                            //checking is some of user space left?
                            if ($usageForecast <= $maxSpace) {
                                $schedDateFrom = date("Y-m-d H:i:s", $fullDateFrom);
                                $schedDateTo = date("Y-m-d H:i:s", $fullDateTo);
                                //creating export schedule
                                $result .= $this->scheduleExportTask($this->myLogin, $channelId, $schedDateFrom, $schedDateTo, $chunksSize);
                            } else {
                                $result .= __('There is not enough space reserved for exporting your records');
                            }
                        } else {
                            $result .= __('No records in archive for this time range');
                        }
                    } else {
                        $result .= __('Wrong time range');
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

        return ($result);
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
            $result['cameraid'] = $explodedName[2];
        }
        return ($result);
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
                $label = wf_tag('center') . wf_img('skins/trash-bin.png') . wf_tag('center', true);
                $label .= wf_delimiter(0);
                $label .= $this->messages->getDeleteAlert();
                $label .= wf_delimiter(0);
                $result .= wf_ConfirmDialog($deleteUrl, web_delete_icon(), $label, '', $cancelUrl, __('Delete') . ' ' . __('Recording') . '?');
            }

            if ($currentModule == 'records') {
                $deleteUrl = self::URL_RECORDS . '&' . self::ROUTE_DELETE . '=' . $fileName;
                $cancelUrl = self::URL_RECORDS;
                $label = wf_tag('center') . wf_img('skins/trash-bin.png') . wf_tag('center', true);
                $label .= wf_delimiter(0);
                $label .= $this->messages->getDeleteAlert();
                $label .= wf_delimiter(0);
                $result .= wf_ConfirmDialog($deleteUrl, web_delete_icon(), $label, '', $cancelUrl, __('Delete') . ' ' . __('Recording') . '?');
            }
        }
        return ($result);
    }

    /**
     * Returns all current user scheduler tasks done, undone or all
     * 
     * @param string $state - all/done/undone
     * 
     * @return array
     */
    protected function scheduleGetMyTasks($state = 'all') {
        $result = array();
        $this->scheduleDb->where('user', '=', $this->myLogin);
        switch ($state) {
            case 'done':
                $this->scheduleDb->where('done', '=', 1);
                break;
            case 'undone':
                $this->scheduleDb->where('done', '=', 0);
                break;
        }
        $result = $this->scheduleDb->getAll();
        return ($result);
    }

    /**
     * Renders scheduled recording exports tasks for current user
     * 
     * @return string
     */
    public function renderScheduledExports() {
        $result = '';
        $allUndoneTasks = $this->scheduleGetMyTasks('undone');
        if (!empty($allUndoneTasks)) {
            $starDust = new StarDust();
            $allExportProcesses = $starDust->getAllStates();

            $cells = wf_TableCell(__('Camera'));
            $cells .= wf_TableCell(__('Time') . ' ' . __('from'));
            $cells .= wf_TableCell(__('Time') . ' ' . __('to'));

            $cells .= wf_TableCell(__('Size forecast'));
            $rows = wf_TableRowStyled($cells, 'row1');
            foreach ($allUndoneTasks as $io => $each) {
                $channelPid = self::PID_EXPORT . $each['channel'];
                $runningLabel = '';
                if (isset($allExportProcesses[$channelPid])) {
                    if (!$allExportProcesses[$channelPid]['finished']) {
                        $runningLabel = ' 🏁 ';
                    }
                }

                $cells = wf_TableCell($this->cameras->getCameraComment($each['channel']) . $runningLabel);
                $cells .= wf_TableCell($each['datetimefrom']);
                $cells .= wf_TableCell($each['datetimeto']);

                $cells .= wf_TableCell(wr_convertSize($each['sizeforecast']), '', '', 'sorttable_customkey="' . $each['sizeforecast'] . '"');
                $rows .= wf_TableRowStyled($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'resp-table sortable');
        }
        return ($result);
    }

    /**
     * Renders howl player for previously generated playlist
     * 
     * @param string $filePath - full file path
     * @param string $width - width in px or %
     * @param bool $autoPlay - start playback right now?
     * @param string $playerId - must be equal to channel name to access playlist in DOM
     * 
     * @return string
     */
    protected function renderRecordPlayer($filePath, $width = '600px', $autoPlay = false, $playerId = '') {
        $player = new Player($width, $autoPlay);
        $result = $player->renderSinglePlayer($filePath, $playerId);
        return ($result);
    }

    /**
     * Renders recording preview with web-player
     * 
     * @return string
     */
    public function renderRecordPreview($filePath) {
        $result = '';
        $webPlayer = '';
        $controls = '';

        if (ubRouting::checkGet(self::ROUTE_BACK_EXPORT)) {
            //back to channel export interface
            $controls .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_CHANNEL . '=' . ubRouting::get(self::ROUTE_BACK_EXPORT)) . ' ';
        } else {
            //just back to records list
            $controls .= wf_BackLink(self::URL_RECORDS) . ' ';
        }


        if (!empty($filePath)) {
            @$filePath = base64_decode($filePath);
            if ($filePath and file_exists($filePath)) {
                $webPlayer .= $this->renderRecordPlayer($filePath, '80%', true, $filePath);
                $controls .= wf_Link($filePath, web_icon_download() . ' ' . __('Download'), false, 'ubButton');
            } else {
                $result .= $this->messages->getStyledMessage(__('File not exists'), 'error');
            }
        }

        $result .= $webPlayer;
        $result .= wf_delimiter(0);
        $result .= $controls;
        return ($result);
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
                $cameraId = $this->cameras->getCameraIdByChannel($channelId);
                $filteredRecordMask = '_' . $cameraId . self::RECORDS_EXT;
                foreach ($allRecords as $io => $each) {
                    if (!ispos($each, $filteredRecordMask)) {
                        unset($allRecords[$io]);
                    }
                }
            }
        }
        if (!empty($allRecords)) {
            $cells = wf_TableCell(__('Camera'));
            $cells .= wf_TableCell(__('Time') . ' ' . __('from'));
            $cells .= wf_TableCell(__('Time') . ' ' . __('to'));
            $cells .= wf_TableCell(__('Size'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($allRecords as $io => $eachFile) {
                $fileNameParts = $this->parseRecordFileName($eachFile);
                $cells = wf_TableCell($this->cameras->getCameraCommentById($fileNameParts['cameraid']));
                $cells .= wf_TableCell($fileNameParts['from']);
                $cells .= wf_TableCell($fileNameParts['to']);

                $recordSize = filesize($userRecordingsDir . $eachFile);
                $recordSizeLabel = wr_convertSize($recordSize);
                $cells .= wf_TableCell($recordSizeLabel, '', '', 'sorttable_customkey="' . $recordSize . '"');
                $actLinks = '';
                $fileUrl = $userRecordingsDir . $eachFile;
                $previewUrl = self::URL_RECORDS . '&' . self::ROUTE_PREVIEW . '=' . base64_encode($fileUrl);
                if ($channelId) {
                    $previewUrl .= '&' . self::ROUTE_BACK_EXPORT . '=' . $channelId;
                }
                $actLinks .= wf_Link($previewUrl, wf_img('skins/icon_play_small.png', __('Show'))) . ' ';
                $actLinks .= wf_Link($fileUrl, web_icon_download()) . ' ';
                $actLinks .= $this->renderRecDelDialog($eachFile) . ' ';
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'resp-table sortable');
        } else {
            $noRecordsNotice = __('You have no saved records yet');
            if ($channelId) {
                $noRecordsNotice .= ' ' . __('for this camera');
            }
            $result .= $this->messages->getStyledMessage($noRecordsNotice, 'info');
        }
        $maxUserSpace = $this->getUserMaxSpace();
        $usedSpaceByMe = $this->getUserUsedSpace($userRecordingsDir);
        $scheduledExportsForecast = $this->scheduleGetForecastSize($this->myLogin);
        $spaceFree = $maxUserSpace - $usedSpaceByMe - $scheduledExportsForecast;
        if ($spaceFree > 0) {
            $spaceLabel = wr_convertSize($spaceFree);
            $notificationType = 'info';
            if ($usedSpaceByMe == 0) {
                $notificationType = 'success';
            }
        } else {
            $spaceLabel = __('Exhausted') . ' :(';
            $notificationType = 'warning';
        }

        $result .= $this->messages->getStyledMessage(__('Free space for saving your records') . ': ' . $spaceLabel, $notificationType);
        return ($result);
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
        return ($result);
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
        return ($result);
    }

    /**
     * Just renders successfull export notification and confirmation
     * 
     * @param string $channelId
     * 
     * @return string
     */
    public function renderExportScheduledNotify($channelId) {
        $result = '';
        $notification = '';
        $notification .= wf_tag('center') . wf_img('skins/checked.png') . wf_tag('center', true);
        $notification .= wf_delimiter(0);

        $notification .= __('Saving recording from the camera is scheduled for you') . '.';
        $notification .= wf_delimiter(0);
        $notification .= __('This will start in a few minutes and may take a while depending on the length of the recording') . '.';
        $notification .= wf_delimiter();
        $notification .= wf_tag('center') . wf_Link(self::URL_ME . '&' . self::ROUTE_CHANNEL . '=' . $channelId, __('Got it') . '!', true, 'confirmagree') . wf_tag('center', true);
        $result .= wf_modalOpenedAuto(__('Success'), $notification);
        return ($result);
    }
}
