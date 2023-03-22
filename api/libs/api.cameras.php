<?php

class Cameras {

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
     * Storages instance placeholder
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
    const ROUTE_DEL = 'deletecameraid';
    const ROUTE_EDIT = 'editcameraid';

    public function __construct() {
        $this->initMessages();
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
        $this->allCameras = $this->camerasDb->getAll('id');
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
            if (!empty($allModels)) {
                $inputs = wf_Selector(self::PROUTE_NEWMODEL, $allModels, __('Model'), '', false) . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWIP, __('IP'), '', false, 12, 'ip') . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWLOGIN, __('Login'), '', false, 12, 'alphanumeric') . ' ';
                $inputs .= wf_TextInput(self::PROUTE_NEWPASS, __('Password'), '', false, 12, '') . ' ';
                $inputs .= wf_CheckInput(self::PROUTE_NEWACT, __('Enabled'), false, true) . ' ';
                $inputs .= wf_Selector(self::PROUTE_NEWSTORAGE, $allStorages, __('Storage'), '', false) . ' ';
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

        $result = zb_rand_string(8);
        while (isset($busyCnannelIds[$result])) {
            $result = zb_rand_string(8);
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
     * 
     * @return void/string on error
     */
    public function create($modelId, $ip, $login, $password, $active, $storageId) {
        $result = '';
        $modelId = ubRouting::filters($modelId, 'int');
        $ipF = ubRouting::filters($ip, 'mres');
        $loginF = ubRouting::filters($login, 'mres');
        $passwordF = ubRouting::filters($password, 'mres');
        $actF = ($active) ? 1 : 0;
        $storageId = ubRouting::filters($storageId, 'int');
        $channelId = $this->getChannelId();

        $allStorages = $this->storages->getAllStorageNames();
        $allModels = $this->models->getAllModelNames();
        if (isset($allStorages[$storageId])) {
            $storageData = $this->storages->getStorageData($storageId);
            $storagePathValid = $this->storages->checkPath($storageData['path']);
            if ($storagePathValid) {
                if (isset($allModels[$modelId])) {
                    if (zb_isIPValid($ipF)) {
                        if (!empty($loginF) AND ! empty($passwordF)) {
                            $this->camerasDb->data('modelid', $modelId);
                            $this->camerasDb->data('ip', $ipF);
                            $this->camerasDb->data('login', $loginF);
                            $this->camerasDb->data('password', $passwordF);
                            $this->camerasDb->data('active', $actF);
                            $this->camerasDb->data('storageid', $storageId);
                            $this->camerasDb->data('channel', $channelId);
                            $this->camerasDb->create();
                            $newId = $this->camerasDb->getLastId();
                            log_register('CAMERA CREATE [' . $newId . ']  MODEL [' . $modelId . '] IP `' . $ip . '` STORAGE [' . $storageId . ']');
                        } else {
                            $result .= __('Login or password is empty');
                        }
                    } else {
                        $result .= __('Wrong IP format') . ': `' . $ip . '`';
                    }
                } else {
                    $result .= __('Storage path is not writable');
                }
            } else {
                $result .= __('Model') . ' [' . $modelId . '] ' . __('not exists');
            }
        } else {
            $result .= __('Storage') . ' [' . $storageId . '] ' . __('not exists');
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
            $allModels = $this->models->getAllModelNames();
            $allStorages = $this->storages->getAllStorageNames();

            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Model'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Enabled'));
            $cells .= wf_TableCell(__('Storage'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allCameras as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($allModels[$each['modelid']]);
                $cells .= wf_TableCell($each['ip']);
                $cells .= wf_TableCell(web_bool_led($each['active']));
                $cells .= wf_TableCell(__($allStorages[$each['storageid']]));
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DEL . '=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
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
            if (!$this->allCameras[$cameraId]['active']) {
                $this->camerasDb->where('id', '=', $cameraId);
                $this->camerasDb->delete();
                log_register('CAMERA DELETE [' . $cameraId . ']');
            } else {
                $result .= __('You cant delete camera which is now active');
            }
        } else {
            $result .= __('Camera not exists') . ' [' . $cameraId . ']';
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

}
