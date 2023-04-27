<?php

/**
 * per-users ACL implementation
 */
class ACL {

    /**
     * Contains ACL database abstraction layer
     *
     * @var object
     */
    protected $aclDb = '';

    /**
     * Contains cameras database abstraction layer
     *
     * @var object
     */
    protected $camerasDb = '';

    /**
     * Contains cameras data as id=>cameraData
     *
     * @var array
     */
    protected $allCamerasData = '';

    /**
     * Contains all available ACLs as aclId=>aclData[id/user/cameraid/channel]
     *
     * @var array
     */
    protected $allAcls = array();

    /**
     * Contains all accessible by all users camerasIds as login=>cameraId=>channel
     *
     * @var array
     */
    protected $accessibleCameras = array();

    /**
     * Contains all accessible by all users channelIds as login=>channelId=>cameraId
     *
     * @var array
     */
    protected $accessibleChannels = array();

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
     * some predefined stuff
     */
    const TABLE_ACL = 'acl';
    const URL_ME = '?module=acl';
    const PROUTE_NEWLOGIN = 'newacllogin';
    const PROUTE_NEWCAMID = 'newaclcameraid';
    const ROUTE_DEL = 'deleteaclid';

    public function __construct($loadCameras = false) {
        $this->initMessages();
        $this->setLogin();
        $this->initAclDb();
        $this->loadAcls();
        if ($loadCameras) {
            $this->initCamerasDb();
            $this->loadCamerasData();
        }
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
     * Inits access control lists database abstraction layer
     * 
     * @return void
     */
    protected function initAclDb() {
        $this->aclDb = new NyanORM(self::TABLE_ACL);
    }

    /**
     * Inits cameras database abstraction layer
     * 
     * @return void
     */
    protected function initCamerasDb() {
        $this->camerasDb = new NyanORM(Cameras::DATA_TABLE);
    }

    /**
     * Loads cameras basic data
     * 
     * @return void
     */
    protected function loadCamerasData() {
        $this->camerasDb->selectable(array('id', 'ip', 'active', 'channel', 'comment'));
        $this->allCamerasData = $this->camerasDb->getAll('id');
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
     * Loads all existing ACL data for further usage
     * 
     * @return void
     */
    protected function loadAcls() {
        $this->allAcls = $this->aclDb->getAll('id');
        if (!empty($this->allAcls)) {
            //some ACL data postprocessing
            foreach ($this->allAcls as $io => $each) {
                $this->accessibleCameras[$each['user']][$each['cameraid']] = $each['channel'];
                $this->accessibleChannels[$each['user']][$each['channel']] = $each['cameraid'];
            }
        }
    }

    /**
     * Creates new ACL database record
     * 
     * @param string $login
     * @param int $cameraId
     * 
     * @return void/string on error
     */
    public function create($login, $cameraId) {
        $result = '';
        $loginF = ubRouting::filters($login, 'mres');
        $cameraId = ubRouting::filters($cameraId, 'int');
        if (isset($this->allCamerasData[$cameraId])) {
            $cameraChannel = $this->allCamerasData[$cameraId]['channel'];
            if (file_exists(USERS_PATH . $loginF)) {
                $this->aclDb->data('user', $loginF);
                $this->aclDb->data('cameraid', $cameraId);
                $this->aclDb->data('channel', $cameraChannel);
                $this->aclDb->create();
                $newId = $this->aclDb->getLastId();
                log_register('ACL CREATE [' . $newId . '] CAMERA [' . $cameraId . '] CHANNEL `' . $cameraChannel . '` FOR {' . $login . '}');
            } else {
                $result .= __('User') . ' {' . $loginF . '} ' . __('not exists');
            }
        } else {
            $result .= __('Camera') . ' [' . $cameraId . '] ' . __('not exists');
        }

        return($result);
    }

    /**
     * Deletes existing ACL from database
     * 
     * @param int $aclId
     * 
     * @return void/string on error
     */
    public function delete($aclId) {
        $result = '';
        $aclId = ubRouting::filters($aclId, 'int');
        if (isset($this->allAcls[$aclId])) {
            $aclData = $this->allAcls[$aclId];
            $this->aclDb->where('id', '=', $aclId);
            $this->aclDb->delete();
            log_register('ACL DELETE [' . $aclId . '] CAMERA [' . $aclData['cameraid'] . '] CHANNEL `' . $aclData['channel'] . '` FOR {' . $aclData['user'] . '}');
        } else {
            $result .= __('ACL') . ' [' . $aclId . '] ' . __('not exists');
        }
        return($result);
    }

    /**
     * Returns availablilty of cameras accessible by user
     * 
     * @return int
     */
    public function haveCamsAssigned() {
        $result = false;
        if (!cfr('ROOT')) {
            if (!empty($this->myLogin)) {
                if (!empty($this->allAcls)) {
                    if (isset($this->accessibleCameras[$this->myLogin])) {
                        $result = true;
                    }
                }
            }
        } else {
            $result = true;
        }
        return($result);
    }

    /**
     * Checks is some camera allowed for current user?
     * 
     * @param int $cameraId
     * 
     * @return bool
     */
    public function isMyCamera($cameraId) {
        $result = false;
        if (!cfr('ROOT')) {
            if (!empty($this->myLogin)) {
                if (!empty($this->allAcls)) {
                    if (isset($this->accessibleCameras[$this->myLogin])) {
                        if (isset($this->accessibleCameras[$this->myLogin][$cameraId])) {
                            $result = true;
                        }
                    }
                }
            }
        } else {
            //all cameras is accessible by ROOT users
            $result = true;
        }
        return($result);
    }

    /**
     * Checks is some camera channel allowed for current user?
     * 
     * @param string $channelId
     * 
     * @return bool
     */
    public function isMyChannel($channelId) {
        $result = false;
        if (!cfr('ROOT')) {
            if (!empty($this->myLogin)) {
                if (!empty($this->allAcls)) {
                    if (isset($this->accessibleChannels[$this->myLogin])) {
                        if (isset($this->accessibleChannels[$this->myLogin][$channelId])) {
                            $result = true;
                        }
                    }
                }
            }
        } else {
            //all cameras is accessible by ROOT users
            $result = true;
        }
        return($result);
    }

    /**
     * Renders existing ACLs list
     * 
     * @return string
     */
    public function renderAclList() {
        $result = '';
        if (!empty($this->allAcls)) {
            $cells = wf_TableCell(__('User'));
            $cells .= wf_TableCell(__('Camera'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allAcls as $io => $each) {
                $cameraLabel = '';
                if (isset($this->allCamerasData[$each['cameraid']])) {
                    $cameraData = $this->allCamerasData[$each['cameraid']];
                    $cameraLabel = $cameraData['ip'] . ' - ' . $cameraData['comment'];
                } else {
                    $cameraLabel = '[' . $each['cameraid'] . '] ' . __('Lost');
                }

                $cells = wf_TableCell($each['user']);
                $cells .= wf_TableCell($cameraLabel, '', '', 'sorttable_customkey="' . $each['cameraid'] . '"');
                $delUrl = self::URL_ME . '&' . self::ROUTE_DEL . '=' . $each['id'];
                $cancelUrl = self::URL_ME;
                $actLinks = wf_ConfirmDialog($delUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', $cancelUrl, __('Delete') . '?');
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
     * Renders new ACL creation form
     * 
     * @return string
     */
    public function renderCreateForm() {
        $result = '';
        $allUsers = rcms_scandir(USERS_PATH);
        $usersParams = array();
        $camerasParams = array();
        if (!empty($allUsers)) {
            foreach ($allUsers as $io => $eachUser) {
                $usersParams[$eachUser] = $eachUser;
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Available users') . ': ' . __('not exists'), 'error');
        }

        if (!empty($this->allCamerasData)) {
            foreach ($this->allCamerasData as $io => $eachCameraData) {
                $camerasParams[$eachCameraData['id']] = $eachCameraData['ip'] . ' - ' . $eachCameraData['comment'];
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Cameras') . ': ' . __('not exists'), 'error');
        }

        //preprocessed data not empty?
        if ($usersParams AND $camerasParams) {
            $inputs = wf_SelectorSearchable(self::PROUTE_NEWLOGIN, $usersParams, __('User'), '', false) . ' ';
            $inputs .= wf_SelectorSearchable(self::PROUTE_NEWCAMID, $camerasParams, __('Camera'), '', false) . ' ';
            $inputs .= wf_Submit(__('Create'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

}
