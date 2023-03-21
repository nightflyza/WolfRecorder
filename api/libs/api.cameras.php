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
            if (isset($allModels[$modelId])) {
                if (zb_isIPValid($ipF)) {
                    if (zb_PingICMP($ipF)) {
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
                        $result .= __('IP') . ' `' . $ip . '`' . __('is not accessible');
                    }
                } else {
                    $result .= __('Wrong IP format') . ': `' . $ip . '`';
                }
            } else {
                $result .= __('Model') . ' [' . $modelId . '] ' . __('not exists');
            }
        } else {
            $result .= __('Storage') . ' [' . $storageId . '] ' . __('not exists');
        }
        return($result);
    }

}
