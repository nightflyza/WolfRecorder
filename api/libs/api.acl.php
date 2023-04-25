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

    public function __construct() {
        $this->initMessages();
        $this->setLogin();
        $this->initAclDb();
        $this->initCamerasDb();
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
        $this->allCamerasData = $this->camerasDb->getAll();
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

}
