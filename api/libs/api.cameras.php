<?php

/**
 * Camera devices management
 */
class Cameras {

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
     * Cameras database abstraction layer placeholder
     *
     * @var object
     */
    protected $camerasDb = '';

    /**
     * Contains all available cameras as id=>cameraData
     *
     * @var array
     */
    protected $allCameras = array();

    /**
     * Camera models instnce placeholder
     * 
     * @var object
     */
    protected $models = '';

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

    /**
     * some predefined stuff here
     */
    const DATA_TABLE = 'cameras';
    const URL_ME = '?module=cameras';
    const PROUTE_NEWMODEL = 'newcameramodelid';
    const PROUTE_NEWIP = 'newcameraip';
    const PROUTE_NEWLOGIN = 'newcameralogin';
    const PROUTE_NEWPASS = 'newcamerapassword';
    const PROUTE_NEWACT = 'newcameraactive';
    const PROUTE_NEWSTORAGE = 'newcamerastorageid';
    const PROUTE_NEWCOMMENT = 'newcameracomment';
    const PROUTE_ED_CAMERAID = 'editcameraid';
    const PROUTE_ED_MODEL = 'editcameramodelid';
    const PROUTE_ED_IP = 'editcameraip';
    const PROUTE_ED_LOGIN = 'editcameralogin';
    const PROUTE_ED_PASS = 'editcamerapassword';
    const PROUTE_ED_STORAGE = 'editcamerastorageid';
    const PROUTE_ED_COMMENT = 'editcameracomment';
    const PROUTE_ED_CAMERAID_ACT = 'renamecameraid';
    const PROUTE_ED_COMMENT_ACT = 'renamecameracomment';
    const ROUTE_DEL = 'deletecameraid';
    const ROUTE_EDIT = 'editcameraid';
    const ROUTE_ACTIVATE = 'activatecameraid';
    const ROUTE_DEACTIVATE = 'deactivatecameraid';
    const CHANNEL_ID_LEN = 11; // 4.738 * 10^18

    /**
     * Dinosaurs are my best friends
     * Through thick and thin, until the very end
     * People tell me, do not pretend
     * Stop living in your made up world again
     * But the dinosaurs, they're real to me
     * They bring me up and make me happy
     * I wish that the world could see
     * The dinosaurs are a part of me
     */

    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->initCamerasDb();
        $this->initStorages();
        $this->initModels();
        $this->loadAllCameras();
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
        $this->binPaths = $ubillingConfig->getBinPaths();
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
     * Inits camera models in protected prop
     * 
     * @return void
     */
    protected function initModels() {
        $this->models = new Models();
    }

    /**
     * Inits database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initCamerasDb() {
        $this->camerasDb = new NyanORM(self::DATA_TABLE);
    }

    /**
     * Loads all existing cameras from database
     * 
     * @return void
     */
    protected function loadAllCameras() {
        $this->camerasDb->orderBy('id', 'DESC');
        $this->allCameras = $this->camerasDb->getAll('id');
    }

    /**
     * Changes comment for existing camera in database
     * 
     * @param int $cameraId
     * @param string $comment
     * 
     * @return void/string
     */
    public function saveComment($cameraId, $comment) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        $commentF = ubRouting::filters($comment, 'mres');
        if (isset($this->allCameras[$cameraId])) {
            $this->camerasDb->where('id', '=', $cameraId);
            $this->camerasDb->data('comment', $commentF);
            $this->camerasDb->save();
            log_register('CAMERA EDIT [' . $cameraId . '] COMMENT `' . $comment . '`');
        } else {
            $result .= __('Camera') . ' [' . $cameraId . '] ' . __('not exists');
        }
        return($result);
    }

    /**
     * Saves changes in existing camera database record
     * 
     * @param int $cameraId
     * @param int $modelId
     * @param string $ip
     * @param string $login
     * @param string $password
     * @param int $storageId
     * @param comment $comment
     * 
     * @return void/string on error
     */
    public function save($cameraId, $modelId, $ip, $login, $password, $storageId, $comment = '') {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        $modelId = ubRouting::filters($modelId, 'int');
        $ipF = ubRouting::filters($ip, 'mres');
        $loginF = ubRouting::filters($login, 'mres');
        $passwordF = ubRouting::filters($password, 'mres');
        $storageId = ubRouting::filters($storageId, 'int');
        $commentF = ubRouting::filters($comment, 'mres');
        if (isset($this->allCameras[$cameraId])) {
            $cameraData = $this->allCameras[$cameraId];
            if ($cameraData['active'] == 0) {
                $allStorages = $this->storages->getAllStorageNames();
                $allModels = $this->models->getAllModelNames();
                if (isset($allStorages[$storageId])) {
                    $storageData = $this->storages->getStorageData($storageId);
                    $storagePathValid = $this->storages->checkPath($storageData['path']);
                    if ($storagePathValid) {
                        if (isset($allModels[$modelId])) {
                            if (zb_isIPValid($ipF)) {
                                if (!empty($loginF) AND ! empty($passwordF)) {
                                    //storage migration?
                                    if ($cameraData['storageid'] != $storageId) {
                                        $this->storages->migrateChannel($storageId, $cameraData['channel']);
                                    }
                                    //updating db
                                    $this->camerasDb->where('id', '=', $cameraId);
                                    $this->camerasDb->data('modelid', $modelId);
                                    $this->camerasDb->data('ip', $ipF);
                                    $this->camerasDb->data('login', $loginF);
                                    $this->camerasDb->data('password', $passwordF);
                                    $this->camerasDb->data('storageid', $storageId);
                                    $this->camerasDb->data('comment', $commentF);
                                    $this->camerasDb->save();
                                    log_register('CAMERA EDIT [' . $cameraId . ']  MODEL [' . $modelId . '] IP `' . $ip . '` STORAGE [' . $storageId . '] COMMENT `' . $comment . '`');
                                } else {
                                    $result .= __('Login or password is empty');
                                }
                            } else {
                                $result .= __('Wrong IP format') . ': `' . $ip . '`';
                            }
                        } else {
                            $result .= __('Model') . ' [' . $modelId . '] ' . __('not exists');
                        }
                    } else {
                        $result .= __('Storage path is not writable');
                    }
                } else {
                    $result .= __('Storage') . ' [' . $storageId . '] ' . __('not exists');
                }
            } else {
                $result .= __('Camera') . ' ' . __('Active') . '!';
            }
        } else {
            $result .= __('Camera') . ' [' . $cameraId . '] ' . __('not exists');
        }
        return($result);
    }

    /**
     * Returns unique channelId
     * 
     * @return string
     */
    protected function getChannelId() {
        $result = '';
        $busyCnannelIds = array();
        if (!empty($this->allCameras)) {
            foreach ($this->allCameras as $io => $each) {
                $busyCnannelIds[$each['channel']] = $each['id'];
            }
        }

        $result = zb_rand_string(self::CHANNEL_ID_LEN);
        while (isset($busyCnannelIds[$result])) {
            $result = zb_rand_string(self::CHANNEL_ID_LEN);
        }

        return($result);
    }

    /**
     * Returns all cameras channels as struct channelId=>cameraId
     * 
     * @return array
     */
    public function getAllCamerasChannels() {
        $result = array();
        if (!empty($this->allCameras)) {
            foreach ($this->allCameras as $io => $each) {
                $result[$each['channel']] = $each['id'];
            }
        }
        return($result);
    }

    /**
     * Returns camera ID by its channel
     * 
     * @param string $channelId
     * 
     * @return int/bool
     */
    public function getCameraIdByChannel($channelId) {
        $result = false;
        $allCameraChannels = $this->getAllCamerasChannels();
        if (isset($allCameraChannels[$channelId])) {
            $result = $allCameraChannels[$channelId];
        }
        return($result);
    }

    /**
     * Checks is camera with some IP already registered or not?
     * 
     * @param string $ip
     * 
     * @return bool
     */
    protected function isCameraIpUsed($ip) {
        $result = false;
        if (!empty($this->allCameras)) {
            foreach ($this->allCameras as $io => $each) {
                if ($ip == $each['ip']) {
                    $result = true;
                }
            }
        }
        return($result);
    }

    /**
     * Creates new camera
     * 
     * @param int $modelId
     * @param string $ip
     * @param string $login
     * @param string $password
     * @param bool $active
     * @param int $storageId
     * @param comment $comment
     * 
     * @return void/string on error
     */
    public function create($modelId, $ip, $login, $password, $active, $storageId, $comment = '') {
        $result = '';
        $modelId = ubRouting::filters($modelId, 'int');
        $ipF = ubRouting::filters($ip, 'mres');
        $loginF = ubRouting::filters($login, 'mres');
        $passwordF = ubRouting::filters($password, 'mres');
        $actF = ($active) ? 1 : 0;
        $storageId = ubRouting::filters($storageId, 'int');
        //automatic storage selection?
        if ($storageId == 0) {
            $storageId = $this->storages->getLeastUsedStorage();
        }
        $commentF = ubRouting::filters($comment, 'mres');
        $channelId = $this->getChannelId();

        $allStorages = $this->storages->getAllStorageNames();
        $allModels = $this->models->getAllModelNames();
        if (isset($allStorages[$storageId])) {
            $storageData = $this->storages->getStorageData($storageId);
            $storagePathValid = $this->storages->checkPath($storageData['path']);
            if ($storagePathValid) {
                if (isset($allModels[$modelId])) {
                    if (zb_isIPValid($ipF)) {
                        if (!$this->isCameraIpUsed($ipF)) {
                            if (!empty($loginF) AND ! empty($passwordF)) {
                                $this->camerasDb->data('modelid', $modelId);
                                $this->camerasDb->data('ip', $ipF);
                                $this->camerasDb->data('login', $loginF);
                                $this->camerasDb->data('password', $passwordF);
                                $this->camerasDb->data('active', $actF);
                                $this->camerasDb->data('storageid', $storageId);
                                $this->camerasDb->data('channel', $channelId);
                                $this->camerasDb->data('comment', $commentF);
                                $this->camerasDb->create();
                                $newId = $this->camerasDb->getLastId();
                                log_register('CAMERA CREATE [' . $newId . ']  MODEL [' . $modelId . '] IP `' . $ip . '` STORAGE [' . $storageId . '] COMMENT `' . $comment . '`');
                            } else {
                                $result .= __('Login or password is empty');
                            }
                        } else {
                            $result .= __('Camera IP already registered');
                        }
                    } else {
                        $result .= __('Wrong IP format') . ': `' . $ip . '`';
                    }
                } else {
                    $result .= __('Model') . ' [' . $modelId . '] ' . __('not exists');
                }
            } else {
                $result .= __('Storage path is not writable');
            }
        } else {
            $result .= __('Storage') . ' [' . $storageId . '] ' . __('not exists');
        }
        return($result);
    }

    /**
     * Returns running cameras recording processes as cameraId=>realPid
     * 
     * @return array
     */
    protected function getRunningRecorders() {
        $result = array();
        $recorderPids = array();
        $command = $this->binPaths['PS'] . ' ax | ' . $this->binPaths['GREP'] . ' ' . $this->binPaths['FFMPG_PATH'] . ' | ' . $this->binPaths['GREP'] . ' -v grep';
        $rawResult = shell_exec($command);
        if (!empty($rawResult)) {
            $rawResult = explodeRows($rawResult);
            foreach ($rawResult as $io => $eachLine) {
                $eachLine = trim($eachLine);
                $rawLine = $eachLine;
                $eachLine = explode(' ', $eachLine);
                if (isset($eachLine[0])) {
                    $eachPid = $eachLine[0];
                    if (is_numeric($eachPid)) {
                        //is this really capture process?
                        if (ispos($rawLine, $this->binPaths['FFMPG_PATH']) AND ispos($rawLine, 'segment_format')) {
                            $recorderPids[$eachPid] = $rawLine;
                        }
                    }
                }
            }
        }

        if (!empty($this->allCameras)) {
            if (!empty($recorderPids)) {
                foreach ($this->allCameras as $eachCameraId => $eachCameraData) {
                    foreach ($recorderPids as $eachPid => $eachProcess) {
                        //looks familiar?
                        if (ispos($eachProcess, $eachCameraData['ip']) AND ispos($eachProcess, $eachCameraData['login'])) {
                            $result[$eachCameraId] = $eachPid;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns running cameras live stream processes as cameraId=>realPid
     * 
     * @return array
     */
    protected function getRunningStreams() {
        $result = array();
        $liveStreamsPids = array();
        $command = $this->binPaths['PS'] . ' ax | ' . $this->binPaths['GREP'] . ' ' . $this->binPaths['FFMPG_PATH'] . ' | ' . $this->binPaths['GREP'] . ' -v grep';
        $rawResult = shell_exec($command);

        if (!empty($rawResult)) {
            $rawResult = explodeRows($rawResult);
            foreach ($rawResult as $io => $eachLine) {
                $eachLine = trim($eachLine);
                $rawLine = $eachLine;
                $eachLine = explode(' ', $eachLine);
                if (isset($eachLine[0])) {
                    $eachPid = $eachLine[0];
                    if (is_numeric($eachPid)) {
                        //is this really live stream process?
                        if (ispos($rawLine, 'hls') AND ispos($rawLine, LiveCams::STREAM_PLAYLIST)) {
                            $liveStreamsPids[$eachPid] = $rawLine;
                        }
                    }
                }
            }
        }

        if (!empty($this->allCameras)) {
            if (!empty($liveStreamsPids)) {
                foreach ($this->allCameras as $eachCameraId => $eachCameraData) {
                    foreach ($liveStreamsPids as $eachPid => $eachProcess) {
                        //looks familiar?
                        if (ispos($eachProcess, $eachCameraData['ip']) AND ispos($eachProcess, $eachCameraData['login'])) {
                            $result[$eachCameraId] = $eachPid;
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Deletes existing camera from database
     * 
     * @param int $cameraId
     * 
     * @return void/string on error
     */
    public function delete($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        //TODO: do something around camera deactivation and checks for running recording
        if (isset($this->allCameras[$cameraId])) {
            $cameraData = $this->allCameras[$cameraId];
            if ($cameraData['active'] == 0) {
                //flushing camera ACLs
                $acl = new ACL();
                $acl->flushCamera($cameraId);
                //deleting camera database record
                $this->camerasDb->where('id', '=', $cameraId);
                $this->camerasDb->delete();
                log_register('CAMERA DELETE [' . $cameraId . ']');
                //flushing camera channel
                $this->storages->flushChannel($cameraData['storageid'], $cameraData['channel']);
            } else {
                $result .= __('You cant delete camera which is now active');
            }
        } else {
            $result .= __('Camera') . ' [' . $cameraId . '] '.__('not exists');
        }

        return($result);
    }

    /**
     * Returns full cameras data with all info required for recorder as struct id=>[CAMERA,TEMPLATE,STORAGE]
     * 
     * @return array
     */
    public function getAllCamerasFullData() {
        $result = array();
        if (!empty($this->allCameras)) {
            $allModelsTemplates = $this->models->getAllModelTemplates();
            $allStoragesData = $this->storages->getAllStoragesData();
            foreach ($this->allCameras as $io => $each) {
                $result[$each['id']]['CAMERA'] = $each;
                $result[$each['id']]['TEMPLATE'] = $allModelsTemplates[$each['modelid']];
                $result[$each['id']]['STORAGE'] = $allStoragesData[$each['storageid']];
            }
        }
        return($result);
    }

    /**
     * Shutdown camera to unlock its settings
     * 
     * @param int $cameraId
     * 
     * @return void/string
     */
    public function deactivate($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCameras[$cameraId])) {
            $recorder = new Recorder();
            //shutdown recording process if it runs
            $recorder->stopRecord($cameraId); //this method locks execution until capture process will be really killed
            //shutdown camera live streams if it runs
            $liveCams = new LiveCams();
            $streamStopResult = $liveCams->stopStream($cameraId);
            if ($streamStopResult) {
                log_register('LIVESTREAM STOPPED [' . $cameraId . ']');
            }
            //disabling camera activity flag
            $this->camerasDb->where('id', '=', $cameraId);
            $this->camerasDb->data('active', 0);
            $this->camerasDb->save();
            log_register('CAMERA DEACTIVATE [' . $cameraId . ']');
        } else {
            $result = __('Camera') . ' [' . $cameraId . '] ' . __('not exists');
        }
        return($result);
    }

    /**
     * Enables camera to lock its settings
     * 
     * @param int $cameraId
     * 
     * @return void/string
     */
    public function activate($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCameras[$cameraId])) {
            //enabling camera activity flag
            $this->camerasDb->where('id', '=', $cameraId);
            $this->camerasDb->data('active', 1);
            $this->camerasDb->save();
            $this->allCameras[$cameraId]['active'] = 1;
            log_register('CAMERA ACTIVATE [' . $cameraId . ']');

            //starting capture now if enabled
            if ($this->altCfg['RECORDER_ON_CAMERA_ACTIVATION']) {
                $recorder = new Recorder();
                $recorder->runRecordBackground($cameraId);
            }
        } else {
            $result = __('Camera') . ' [' . $cameraId . '] ' . __('not exists');
        }
        return($result);
    }

    /**
     * Returns camera comment or IP by its channelId
     * 
     * @param string $channelId
     * 
     * @return string
     */
    public function getCameraComment($channelId) {
        $result = '';
        if (!empty($this->allCameras)) {
            foreach ($this->allCameras as $io => $each) {
                if ($each['channel'] == $channelId) {
                    if (!empty($each['comment'])) {
                        $result = $each['comment'];
                    } else {
                        $result = $each['ip'];
                    }
                }
            }
        }
        if (empty($result)) {
            $result = __('Lost');
        }
        return($result);
    }

    /**
     * Returns camera comment or IP by its cameraId
     * 
     * @param string $cameraId
     * 
     * @return string
     */
    public function getCameraCommentById($cameraId) {
        $result = '';
        if (!empty($this->allCameras)) {
            foreach ($this->allCameras as $io => $each) {
                if ($each['id'] == $cameraId) {
                    if (!empty($each['comment'])) {
                        $result = $each['comment'];
                    } else {
                        $result = $each['ip'];
                    }
                }
            }
        }
        if (empty($result)) {
            $result = __('Lost');
        }
        return($result);
    }

    /**
     * Checks is camera with some IP registered or not?
     * 
     * @param string $ip
     * 
     * @return int/bool
     */
    public function isRegisteredIp($ip) {
        $result = false;
        if (!empty($this->allCameras)) {
            foreach ($this->allCameras as $io => $each) {
                if ($each['ip'] == $ip) {
                    $result = $each['id'];
                    break;
                }
            }
        }
        return($result);
    }

    /**
     * Returns camera creation form
     * 
     * @return string
     */
    public function renderCreateForm() {
        $result = '';
        $allStorages = $this->storages->getAllStorageNames();
        $allModels = $this->models->getAllModelNames();
        if (!empty($allStorages)) {
            $storagesParams = array();
            $storagesParams = array(0 => __('Auto'));
            foreach ($allStorages as $eachStorageId => $eachStorageName) {
                $storagesParams[$eachStorageId] = __($eachStorageName);
            }

            if (!empty($allModels)) {
                $inputs = wf_Selector(self::PROUTE_NEWMODEL, $allModels, __('Model'), '', true) . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWIP, __('IP'), '', true, 12, 'ip') . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWLOGIN, __('Login'), '', true, 14, 'alphanumeric') . ' ';
                $inputs .= wf_PasswordInput(self::PROUTE_NEWPASS, __('Password'), '', true, 14) . ' ';
                $inputs .= wf_CheckInput(self::PROUTE_NEWACT, __('Enabled'), true, true) . ' ';
                $inputs .= wf_Selector(self::PROUTE_NEWSTORAGE, $storagesParams, __('Storage'), '', true) . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWCOMMENT, __('Description'), '', true, 18, '') . ' ';
                $inputs .= wf_Submit(__('Create'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('Any device models exists'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Any storages exists'), 'error');
        }
        return($result);
    }

    /**
     * Returns camera editing form
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    public function renderEditForm($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        $allStorages = $this->storages->getAllStorageNames();
        $allModels = $this->models->getAllModelNames();

        if (isset($this->allCameras[$cameraId])) {
            $cameraData = $this->allCameras[$cameraId];
            if (!empty($allStorages)) {
                $storagesParams = array();
                foreach ($allStorages as $eachStorageId => $eachStorageName) {
                    $storagesParams[$eachStorageId] = __($eachStorageName);
                }
                if (!empty($allModels)) {
                    $inputs = wf_HiddenInput(self::PROUTE_ED_CAMERAID, $cameraId);
                    $inputs .= wf_Selector(self::PROUTE_ED_MODEL, $allModels, __('Model'), $cameraData['modelid'], true) . ' ';
                    $inputs .= wf_TextInput(self::PROUTE_ED_IP, __('IP'), $cameraData['ip'], true, 12, 'ip') . ' ';
                    $inputs .= wf_TextInput(self::PROUTE_ED_LOGIN, __('Login'), $cameraData['login'], true, 14, 'alphanumeric') . ' ';
                    $inputs .= wf_PasswordInput(self::PROUTE_ED_PASS, __('Password'), $cameraData['password'], true, 14) . ' ';
                    $inputs .= wf_Selector(self::PROUTE_ED_STORAGE, $storagesParams, __('Storage'), $cameraData['storageid'], true) . ' ';
                    $inputs .= wf_TextInput(self::PROUTE_ED_COMMENT, __('Description'), $cameraData['comment'], true, 18, '') . ' ';
                    $inputs .= wf_Submit(__('Save'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Any device models exists'), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Any storages exists'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Camera') . ' [' . $cameraId . '] ' . __('not exists'), 'error');
        }
        return($result);
    }

    /**
     * Returns camera comments editing form
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    public function renderRenameForm($cameraId) {
        $result = '';
        $cameraId = ubRouting::filters($cameraId, 'int');

        if (isset($this->allCameras[$cameraId])) {
            $cameraData = $this->allCameras[$cameraId];
            $inputs = wf_HiddenInput(self::PROUTE_ED_CAMERAID_ACT, $cameraId);
            $inputs .= wf_TextInput(self::PROUTE_ED_COMMENT_ACT, __('Description'), $cameraData['comment'], true, 20, '') . ' ';
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            $result .= __('You can only change the description of cameras that are active at this moment');
        } else {
            $result .= $this->messages->getStyledMessage(__('Camera') . ' [' . $cameraId . '] ' . __('not exists'), 'error');
        }
        return($result);
    }

    /**
     * Renders available cameras list
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        if (!empty($this->allCameras)) {
            $totalCount = 0;
            $allRunningRecorders = $this->getRunningRecorders();
            $allLiveStreams = $this->getRunningStreams();
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Enabled'));
            $cells .= wf_TableCell(__('Recording'));
            $cells .= wf_TableCell(__('Live'));
            $cells .= wf_TableCell(__('Description'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allCameras as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['ip'], '', '', 'sorttable_customkey="' . ip2int($each['ip']) . '"');
                $cells .= wf_TableCell(web_bool_led($each['active']), '', '', 'sorttable_customkey="' . $each['active'] . '"');
                $recordingFlag = isset($allRunningRecorders[$each['id']]) ? 1 : 0;
                $cells .= wf_TableCell(web_bool_led($recordingFlag), '', '', 'sorttable_customkey="' . $recordingFlag . '"');
                $liveFlag = isset($allLiveStreams[$each['id']]) ? 1 : 0;
                $cells .= wf_TableCell(web_bool_led($liveFlag), '', '', 'sorttable_customkey="' . $liveFlag . '"');
                $cells .= wf_TableCell($each['comment']);
                $actLinks = wf_Link(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $each['id'], web_edit_icon(), false);
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
                $totalCount++;
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
            $result .= wf_tag('b') . __('Total') . ': ' . $totalCount . wf_tag('b', true);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
    }

    /**
     * Renders camera profile
     * 
     * @param int $cameraId
     * 
     * @return string
     */
    public function renderCameraProfile($cameraId) {
        $result = '';
        $cameraControls = '';
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCameras[$cameraId])) {
            $allModels = $this->models->getAllModelNames();
            $allStorages = $this->storages->getAllStorageNames();
            $cameraData = $this->allCameras[$cameraId];

            //recorder process now is running?
            $allRunningRecorders = $this->getRunningRecorders();
            $recordingFlag = (isset($allRunningRecorders[$cameraId])) ? 1 : 0;

            //live stream process now is running?
            $allRunningLiveStreams = $this->getRunningStreams();
            $liveStreamFlag = (isset($allRunningLiveStreams[$cameraId])) ? 1 : 0;

            //some channel data collecting
            $channelChunks = $this->storages->getChannelChunks($cameraData['storageid'], $cameraData['channel']);
            $chunksCount = sizeof($channelChunks);
            $archiveDepth = '-';
            $archiveSeconds = 0;
            if ($chunksCount > 0) {
                $archiveSeconds = $this->altCfg['RECORDER_CHUNK_TIME'] * $chunksCount;
                $archiveDepth = wr_formatTimeArchive($archiveSeconds);
            }

            $chanSizeRaw = $this->storages->getChannelChunksSize($channelChunks);
            $chanSizeLabel = wr_convertSize($chanSizeRaw);

            $chanBitrateLabel = '-';
            if ($archiveSeconds AND $chanSizeRaw) {
                $chanBitrate = ($chanSizeRaw * 8) / $archiveSeconds / 1024; // in kbits
                $chanBitrateLabel = round(($chanBitrate / 1024), 2) . ' ' . __('Mbit/s');
            }

            //camera profile here
            $cells = wf_TableCell(__('Model'), '40%', 'row2');
            $cells .= wf_TableCell($allModels[$cameraData['modelid']]);
            $rows = wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('IP'), '', 'row2');
            $cells .= wf_TableCell($cameraData['ip']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Login'), '', 'row2');
            $cells .= wf_TableCell($cameraData['login']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Password'), '', 'row2');
            $cells .= wf_TableCell($cameraData['password']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Enabled'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($cameraData['active']));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Recording'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($recordingFlag));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Live'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($liveStreamFlag));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Description'), '', 'row2');
            $cells .= wf_TableCell($cameraData['comment']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Storage'), '', 'row2');
            $cells .= wf_TableCell(__($allStorages[$cameraData['storageid']]));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Channel'), '', 'row2');
            $cells .= wf_TableCell($cameraData['channel']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Archive depth'), '', 'row2');
            $cells .= wf_TableCell($archiveDepth);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Average bitrate'), '', 'row2');
            $cells .= wf_TableCell($chanBitrateLabel);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Size'), '', 'row2');
            $cells .= wf_TableCell($chanSizeLabel);
            $rows .= wf_TableRow($cells, 'row3');



            $result .= wf_TableBody($rows, '100%', 0, 'resp-table');

            //some controls here


            if ($cameraData['active']) {
                $deactUrl = self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $cameraData['id'] . '&' . self::ROUTE_DEACTIVATE . '=' . $cameraData['id'];
                $cameraControls .= wf_Link($deactUrl, web_bool_led(0) . ' ' . __('Disable'), false, 'ubButton') . ' ';
            } else {
                $cameraControls .= wf_Link(self::URL_ME . '&' . self::ROUTE_ACTIVATE . '=' . $cameraData['id'], web_bool_led(1) . ' ' . __('Enable'), false, 'ubButton') . ' ';
            }

            if ($cameraData['active']) {
                if (cfr('LIVECAMS')) {
                    $cameraControls .= wf_Link(LiveCams::URL_ME . '&' . LiveCams::ROUTE_VIEW . '=' . $cameraData['channel'], wf_img('skins/icon_live_small.png') . ' ' . __('Live'), false, 'ubButton');
                }
            }

            if (cfr('ARCHIVE')) {
                $cameraControls .= wf_Link(Archive::URL_ME . '&' . Archive::ROUTE_VIEW . '=' . $cameraData['channel'], wf_img('skins/icon_archive_small.png') . ' ' . __('Video from camera'), false, 'ubButton');
            }

            if (cfr('EXPORT')) {
                $cameraControls .= wf_Link(Export::URL_ME . '&' . Export::ROUTE_CHANNEL . '=' . $cameraData['channel'], wf_img('skins/icon_export.png') . ' ' . __('Save record'), false, 'ubButton');
            }

            if (!$cameraData['active']) {
                //editing interface here
                $editingForm = $this->renderEditForm($cameraId);
                $cameraControls .= wf_modalAuto(web_edit_icon() . ' ' . __('Edit'), __('Edit camera') . ': ' . $cameraData['comment'], $editingForm, 'ubButton');

                //deletion interface here
                $deletionUrl = self::URL_ME . '&' . self::ROUTE_DEL . '=' . $cameraId;
                $cancelUrl = self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $cameraId;
                $deletionAlert = $this->messages->getDeleteAlert() . '. ' . wf_tag('br');
                $deletionAlert .= __('Also all archive data for this camera will be destroyed permanently') . '.';
                $deletionTitle = __('Delete') . ' ' . __('Camera') . ' ' . $cameraData['ip'] . '?';
                $cameraControls .= wf_ConfirmDialog($deletionUrl, web_delete_icon() . ' ' . __('Delete'), $deletionAlert, 'ubButton', $cancelUrl, $deletionTitle) . ' ';
            } else {
                //only comments editing accessible for active cameras
                $renameForm = $this->renderRenameForm($cameraId);
                $cameraControls .= wf_modalAuto(web_edit_icon() . ' ' . __('Edit'), __('Edit camera') . ': ' . $cameraData['comment'], $renameForm, 'ubButton');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Camera') . ' [' . $cameraId . '] ' . __('not exists'), 'error');
        }


        $result .= wf_delimiter(0);
        $result .= wf_BackLink(self::URL_ME) . ' ';
        $result .= $cameraControls;
        return($result);
    }

}
