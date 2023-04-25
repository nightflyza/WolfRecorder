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
     * Contains all available ACLs as user=>aclData[id/user/cameraid/channel]
     *
     * @var array
     */
    protected $allAcls = array();

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

    public function __construct($loadCameras = false) {
        $this->initMessages();
        $this->setLogin();
        $this->initAclDb();
        if ($loadCameras) {
            $this->initCamerasDb();
            $this->loadCamerasData();
        }
        $this->loadAcls();
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
        $this->allAcls = $this->aclDb->getAll('user');
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
                    foreach ($this->allAcls as $eachUserLogin => $eachAclData) {
                        if ($eachUserLogin == $this->myLogin AND $eachAclData['cameraid'] == $cameraId) {
                            $result = true;
                            break;
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
                    foreach ($this->allAcls as $eachUserLogin => $eachAclData) {
                        if ($eachUserLogin == $this->myLogin AND $eachAclData['channel'] == $channelId) {
                            $result = true;
                            break;
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
                $cells .= wf_TableCell('TODO');
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
    }

}
