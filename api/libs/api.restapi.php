<?php

/**
 * REST API v1 implementation
 */
class RestAPI {

    /**
     * Contains available objects and their methods callbacks as object=>callback=>methodName
     *
     * @var array
     */
    protected $objects = array();

    /**
     * Some predefined stuff
     */
    const PROUTE_DATA = 'data';

    /**
     * Oo
     */
    public function __construct() {
        $this->setAvailableObjects();
    }

    /**
     * Sets available objects and their methods
     * 
     * @return void
     */
    protected function setAvailableObjects() {
        $this->objects = array(
            'models' => array(
                'getall' => 'modelsGetAll'
            ),
            'storages' => array(
                'getall' => 'storagesGetAll',
                'getstates' => 'storagesGetStates'
            ),
            'cameras' => array(
                'getall' => 'camerasGetAll',
                'create' => 'camerasCreate',
                'activate' => 'camerasActivate',
                'deactivate' => 'camerasDeactivate',
                'setdescription' => 'camerasSetDescription',
                'delete' => 'camerasDelete',
                'isregistered' => 'camerasIsRegistered'
            ),
            'users' => array(
                'getall' => 'usersGetAll',
                'create' => 'usersCreate',
                'delete' => 'usersDelete',
                'changepassword' => 'usersChangePassword',
                'isregistered' => 'usersIsRegistered',
                'checkauth' => 'usersCheckAuth'
            ),
            'acls' => array(
                'getall' => 'aclsGetAll',
                'getallchannels' => 'aclsGetAllChannels',
                'getallcameras' => 'aclsGetAllCameras',
                'getchannels' => 'aclsGetChannels',
                'getcameras' => 'aclsGetCameras',
                'assignchannel' => 'aclsAssignChannel',
                'assigncamera' => 'aclsAssignCamera',
                'deassignchannel' => 'aclsDeassignChannel',
                'deassigncamera' => 'aclsDeassignCamera',
            ),
            'channels' => array(
                'getall' => 'channelsGetAll',
                'getscreenshotsall' => 'channelsGetScreenshotsAll',
                'getscreenshot' => 'channelsGetScreenshot',
                'getlivestream' => 'channelsGetLiveStream',
            ),
            'recorders' => array(
                'getall' => 'recordersGetAll',
                'isrunning' => 'recordersIsRunning'
            ),
            'system' => array(
                'gethealth' => 'systemGetHealth',
                'checkconnection' => 'systemCheckConnection'
            ),
        );
    }

    /**
     * Performs check for availability of request fields
     * 
     * @param array $required
     * @param array $data
     * 
     * @return bool
     */
    protected function checkRequestFields($required, $data) {
        $result = true;
        if (!empty($required)) {
            if (!empty($data)) {
                foreach ($required as $io => $eachRequired) {
                    if (!isset($data[$eachRequired])) {
                        $result = false;
                    }
                }
            } else {
                $result = false;
            }
        }
        return($result);
    }

    /**
     * Catches some object method callback
     * 
     * @return void
     */
    public function catchRequest() {
        $result = array(
            'error' => 1,
            'message' => __('No object specified')
        );
        if (!empty($this->objects)) {
            foreach ($this->objects as $eachObject => $objectMethods) {
                //object call
                if (ubRouting::checkGet($eachObject)) {
                    $methodCallback = ubRouting::get($eachObject);
                    if (isset($objectMethods[$methodCallback])) {
                        $methodName = $objectMethods[$methodCallback];
                        if (method_exists($this, $methodName)) {
                            $inputData = array();
                            if (ubRouting::checkPost(self::PROUTE_DATA)) {
                                $unpackData = json_decode(ubRouting::post(self::PROUTE_DATA), true);
                                if (is_array($unpackData)) {
                                    $inputData = $unpackData;
                                }
                            }
                            $result = $this->$methodName($inputData);
                        } else {
                            $result = array(
                                'error' => 2,
                                'message' => __('Method not exists')
                            );
                        }
                    }
                }
            }
        }
        $this->renderReply($result);
    }

    /**
     * Returns request reply as JSON
     * 
     * @return void
     */
    protected function renderReply($data) {
        header('Content-Type: application/json; charset=UTF-8');
        $data = json_encode($data);
        die($data);
    }

    ///////////////////////////
    // Camera models methods //
    ///////////////////////////

    /**
     * Returns available camera models list
     * 
     * @return array
     */
    protected function modelsGetAll() {
        $models = new Models();
        $result = $models->getAllModelData();
        return($result);
    }

    //////////////////////
    // Storages methods //
    //////////////////////

    /**
     * Returns available storages list
     * 
     * @return array
     */
    protected function storagesGetAll() {
        $storages = new Storages();
        $result = $storages->getAllStoragesData();
        return($result);
    }

    /**
     * Returns available storages states
     * 
     * @return array
     */
    protected function storagesGetStates() {
        $result = array();
        $storages = new Storages();
        $allStorages = $storages->getAllStoragesData();
        if (!empty($allStorages)) {
            foreach ($allStorages as $io => $each) {
                $storageState = ($storages->checkPath($each['path'])) ? 1 : 0;
                $storageTotal = @disk_total_space($each['path']);
                $storageFree = @disk_free_space($each['path']);
                $storageUsed = $storageTotal - $storageFree;
                $result[$each['id']]['state'] = $storageState;
                $result[$each['id']]['total'] = $storageTotal;
                $result[$each['id']]['used'] = $storageUsed;
                $result[$each['id']]['free'] = $storageFree;
            }
        }
        return($result);
    }

    /////////////////////
    // Cameras methods //
    /////////////////////

    /**
     * Returns full available cameras data
     * 
     * @return array
     */
    protected function camerasGetAll() {
        $cameras = new Cameras();
        $result = $cameras->getAllCamerasFullData();
        return($result);
    }

    /**
     * Returns list of cameras accessible by
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function camerasCreate($request) {
        $result = array();
        $requiredFields = array('modelid', 'ip', 'login', 'password', 'active', 'storageid', 'description');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $cameras = new Cameras();
            $modelId = $request['modelid'];
            $ip = $request['ip'];
            $login = $request['login'];
            $password = $request['password'];
            $active = $request['active'];
            $storageId = $request['storageid'];
            $comment = $request['description'];
            $creationResult = $cameras->create($modelId, $ip, $login, $password, $active, $storageId, $comment);
            if (!empty($creationResult)) {
                $result = array('error' => 7, 'message' => $creationResult);
            } else {
                $result = array('error' => 0, 'message' => __('Success'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Activates existing camera
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function camerasActivate($request) {
        $result = array();
        $requiredFields = array('cameraid');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $cameras = new Cameras();
            $actResult = $cameras->activate($request['cameraid']);
            if (!empty($actResult)) {
                $result = array('error' => 7, 'message' => $actResult);
            } else {
                $result = array('error' => 0, 'message' => __('Success'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Deactivates existing camera
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function camerasDeactivate($request) {
        $result = array();
        $requiredFields = array('cameraid');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $cameras = new Cameras();
            $deactResult = $cameras->deactivate($request['cameraid']);
            if (!empty($deactResult)) {
                $result = array('error' => 7, 'message' => $deactResult);
            } else {
                $result = array('error' => 0, 'message' => __('Success'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Sets camera description
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function camerasSetDescription($request) {
        $result = array();
        $requiredFields = array('cameraid', 'description');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $cameras = new Cameras();
            $renameResult = $cameras->saveComment($request['cameraid'], $request['description']);
            if (empty($renameResult)) {
                $result = array('error' => 0, 'message' => __('Success'));
            } else {
                $result = array('error' => 7, 'message' => $renameResult);
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Deletes existing camera
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function camerasDelete($request) {
        $result = array();
        $requiredFields = array('cameraid');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $cameras = new Cameras();
            $delResult = $cameras->delete($request['cameraid']);
            if (!empty($delResult)) {
                $result = array('error' => 7, 'message' => $delResult);
            } else {
                $result = array('error' => 0, 'message' => __('Success'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Checks is camera registered or not by its IP
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function camerasIsRegistered($request) {
        $result = array();
        $requiredFields = array('ip');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $cameras = new Cameras();
            $registerId = $cameras->isRegisteredIp($request['ip']);
            $registerState = ($registerId) ? 1 : 0;
            $result = array('error' => 0, 'registered' => $registerState, 'id' => $registerId);
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    ///////////////////////////
    // System object methods //
    ///////////////////////////

    /**
     * Just dummy callback to check API connection
     * 
     * @return array
     */
    protected function systemCheckConnection() {
        $result = array('error' => 0, 'connection' => 1, 'message' => __('Success'));
        return($result);
    }

    /**
     * Returns system health info
     * 
     * @return array
     */
    protected function systemGetHealth() {
        global $ubillingConfig;
        $result = array(
            'storages' => 1,
            'network' => 1,
            'database' => 1,
            'channels_total' => 0,
            'channels_online' => 0,
            'uptime' => 0,
            'loadavg' => 0,
        );

        //storages diag
        $storagesStates = $this->storagesGetStates();
        if (!empty($storagesStates)) {
            foreach ($storagesStates as $io => $eachStorageState) {
                if (!$eachStorageState['state']) {
                    $result['storages'] = 0;
                }
            }
        } else {
            $result['storages'] = 0;
        }

        //network diag
        if (!zb_PingICMP('wolfrecorder.com')) {
            $result['network'] = 0;
        }

        //cameras stats
        $allCamerasData = $this->camerasGetAll();
        $result['channels_total'] = sizeof($allCamerasData);
        if (!empty($allCamerasData)) {
            $recorder = new Recorder();
            $runningRecorders = $recorder->getRunningRecorders();
            $result['channels_online'] = sizeof($runningRecorders);
        }

        //system uptime
        $binPaths = $ubillingConfig->getBinpaths();
        $rawUptime = shell_exec($binPaths['UPTIME']);
        $uptime = '';
        if (!empty($rawUptime)) {
            $rawUptime = explode(',', $rawUptime);
            $uptime = trim($rawUptime[0]);
            if (ispos($uptime, 'up')) {
                $uptimeClean = explode('up', $uptime);
                if (isset($uptimeClean[1])) {
                    $uptime = trim($uptimeClean[1]);
                }
            }
        }
        $result['uptime'] = $uptime;
        //system load
        $loadAvg = sys_getloadavg();
        $result['loadavg'] = round($loadAvg[0], 2);
        return($result);
    }

    ///////////////////////////
    // Users object methods  //
    ///////////////////////////

    /**
     * Returns list of all available user data
     * 
     * @return array
     */
    protected function usersGetAll() {
        $userManager = new UserManager();
        return($userManager->getAllUsersData());
    }

    /**
     * Creates new limited user
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function usersCreate($request) {
        $result = array();
        $requiredFields = array('login', 'password');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $userManager = new UserManager();
            $login = $request['login'];
            $password = $request['password'];
            $role = 'LIMITED';
            $userRegResult = $userManager->createUser($login, $password, $password, $role);
            if (!empty($userRegResult)) {
                $result = array('error' => 7, 'message' => $userRegResult);
            } else {
                $result = array('error' => 0, 'message' => __('Success'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Changes some existing user password to new one
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function usersChangePassword($request) {
        $result = array();
        $requiredFields = array('login', 'password');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $userManager = new UserManager();
            $login = $request['login'];
            $password = $request['password'];
            $userSaveResult = $userManager->saveUser($login, $password, $password);
            if (!empty($userSaveResult)) {
                $result = array('error' => 7, 'message' => $userSaveResult);
            } else {
                $result = array('error' => 0, 'message' => __('Success'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Checks is user registered or not?
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function usersIsRegistered($request) {
        $result = array();
        $requiredFields = array('login');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $userManager = new UserManager();
            $login = $request['login'];
            $userCheckResult = ($userManager->isUserRegistered($login)) ? 1 : 0;
            $result = array('error' => 0, 'registered' => $userCheckResult);
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Deletes an existing user
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function usersDelete($request) {
        $result = array();
        $requiredFields = array('login');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $userManager = new UserManager();
            $login = $request['login'];
            if ($userManager->isUserRegistered($login)) {
                $userManager->deleteUser($login);
                $result = array('error' => 0, 'message' => __('Success'));
            } else {
                $result = array('error' => 7, 'message' => __('User') . ' ' . __('not exists'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Checks can user be athorized with some login and password or not
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function usersCheckAuth($request) {
        $result = array();
        $requiredFields = array('login', 'password');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $login = $request['login'];
            $password = $request['password'];
            if (!empty($login) AND ! empty($password)) {
                $userManager = new UserManager();
                $allUsersData = $userManager->getAllUsersData();
                if (isset($allUsersData[$login])) {
                    $userData = $allUsersData[$login];
                    if (!empty($userData)) {
                        $passwordHash = md5($password);
                        if ($passwordHash == $userData['password']) {
                            $result = array('error' => 0, 'auth' => 1, 'message' => __('Success'));
                        } else {
                            $result = array('error' => 6, 'auth' => 0, 'message' => __('Wrong credentials'));
                        }
                    } else {
                        $result = array('error' => 7, 'message' => __('Error reading user profile'));
                    }
                } else {
                    $result = array('error' => 6, 'auth' => 0, 'message' => __('Wrong credentials'));
                }
            } else {
                $result = array('error' => 7, 'message' => __('Login') . ' ' . __('or') . ' ' . __('password') . ' ' . __('is empty'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    ///////////////////
    // ACLs methods  //
    ///////////////////

    /**
     * Returns array of all available ACLs
     * 
     * @return array
     */
    protected function aclsGetAll() {
        $result = array();
        $acl = new ACL();
        $result = $acl->getAllAclsData();
        return($result);
    }

    /**
     * Returns array of all available user to cameras ACLs
     * 
     * @return array
     */
    protected function aclsGetAllCameras() {
        $result = array();
        $acl = new ACL();
        $result = $acl->getAllCameraAclsData();
        return($result);
    }

    /**
     * Returns array of all available user to channels ACLs
     * 
     * @return array
     */
    protected function aclsGetAllChannels() {
        $result = array();
        $acl = new ACL();
        $result = $acl->getAllChannelAclsData();
        return($result);
    }

    /**
     * Returns all channels assigned to some user as channelId=>cameraId
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function aclsGetChannels($request) {
        $result = array();
        $requiredFields = array('login');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $login = $request['login'];
            $acl = new ACL();
            $allChannelAcls = $acl->getAllChannelAclsData();
            if (isset($allChannelAcls[$login])) {
                $result = $allChannelAcls[$login];
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Returns all camearas assigned to some user as cameraId=>channelId
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function aclsGetCameras($request) {
        $result = array();
        $requiredFields = array('login');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $login = $request['login'];
            $acl = new ACL();
            $allCameraAcls = $acl->getAllCameraAclsData();
            if (isset($allCameraAcls[$login])) {
                $result = $allCameraAcls[$login];
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Creates ACL for some user by cameraId
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function aclsAssignCamera($request) {
        $result = array();
        $requiredFields = array('login', 'cameraid');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $login = $request['login'];
            $cameraId = $request['cameraid'];
            $acl = new ACL(true);
            $aclCreationResult = $acl->create($login, $cameraId);
            if (empty($aclCreationResult)) {
                $result = array('error' => 0, 'message' => __('Success'));
            } else {
                $result = array('error' => 7, 'message' => $aclCreationResult);
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Creates ACL for some user by channelId
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function aclsAssignChannel($request) {
        $result = array();
        $requiredFields = array('login', 'channelid');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $login = $request['login'];
            $channelId = $request['channelid'];
            $acl = new ACL(true);
            $aclCreationResult = $acl->assignChannel($login, $channelId);
            if (empty($aclCreationResult)) {
                $result = array('error' => 0, 'message' => __('Success'));
            } else {
                $result = array('error' => 7, 'message' => $aclCreationResult);
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Deletes ACL for some user by cameraId
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function aclsDeassignCamera($request) {
        $result = array();
        $requiredFields = array('login', 'cameraid');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $login = $request['login'];
            $cameraId = $request['cameraid'];
            $aclDeletionId = 0;
            $acl = new ACL();
            $allAcls = $acl->getAllAclsData();
            if (!empty($allAcls)) {
                foreach ($allAcls as $io => $each) {
                    if ($each['user'] == $login AND $each['cameraid'] == $cameraId) {
                        $aclDeletionId = $each['id'];
                    }
                }
            }

            if ($aclDeletionId) {
                $aclDeletionResult = $acl->delete($aclDeletionId);
                if (empty($aclDeletionResult)) {
                    $result = array('error' => 0, 'message' => __('Success'));
                } else {
                    $result = array('error' => 7, 'message' => $aclDeletionResult);
                }
            } else {
                $result = array('error' => 0, 'message' => __('ACL') . ' ' . __('not exists'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Deletes ACL for some user by channelId
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function aclsDeassignChannel($request) {
        $result = array();
        $requiredFields = array('login', 'channelid');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $login = $request['login'];
            $channelId = $request['channelid'];
            $aclDeletionId = 0;
            $acl = new ACL();
            $allAcls = $acl->getAllAclsData();
            if (!empty($allAcls)) {
                foreach ($allAcls as $io => $each) {
                    if ($each['user'] == $login AND $each['channel'] == $channelId) {
                        $aclDeletionId = $each['id'];
                    }
                }
            }

            if ($aclDeletionId) {
                $aclDeletionResult = $acl->delete($aclDeletionId);
                if (empty($aclDeletionResult)) {
                    $result = array('error' => 0, 'message' => __('Success'));
                } else {
                    $result = array('error' => 7, 'message' => $aclDeletionResult);
                }
            } else {
                $result = array('error' => 0, 'message' => __('ACL') . ' ' . __('not exists'));
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    ///////////////////////
    // Channels methods  //
    ///////////////////////

    /**
     * Returns all available channels as channelId=>cameraId
     * 
     * @return array
     */
    protected function channelsGetAll() {
        $result = array();
        $cameras = new Cameras();
        $result = $cameras->getAllCamerasChannels();
        return($result);
    }

    /**
     * Returns latest channel screenshot
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function channelsGetScreenshot($request) {
        $result = array();
        $requiredFields = array('channelid');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $channelId = $request['channelid'];
            $chanshots = new ChanShots();
            $screenshotRaw = $chanshots->getChannelScreenShot($channelId);
            $result = array('error' => 0, 'screenshot' => $screenshotRaw);
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

    /**
     * Returns latest all channels screenshot as channelId=>screenshotUrl
     * 
     * @return array
     */
    protected function channelsGetScreenshotsAll() {
        $result = array();
        $cameras = new Cameras();
        $allCamerasChannels = $cameras->getAllCamerasChannels();

        if (!empty($allCamerasChannels)) {
            $chanshots = new ChanShots();
            foreach ($allCamerasChannels as $eachChannelId => $eachCameraId) {
                $eachScreenShotUrl = $chanshots->getChannelScreenShot($eachChannelId);
                if (!empty($eachScreenShotUrl)) {
                    $result[$eachChannelId] = $eachScreenShotUrl;
                }
            }
        }

        return($result);
    }

    ///////////////////////
    // Recorders methods //
    ///////////////////////

    /**
     * Returns all running recorders as cameraId=>PID
     * 
     * @return array
     */
    protected function recordersGetAll() {
        $result = array();
        $recorders = new Recorder();
        $result = $recorders->getRunningRecorders();
        return($result);
    }

    /**
     * Returns state of running recorder for some camera
     * 
     * @param array $request
     * 
     * @return array
     */
    protected function recordersIsRunning($request) {
        $requiredFields = array('cameraid');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $cameraId = $request['cameraid'];
            $recorders = new Recorder();
            $allRunning = $recorders->getRunningRecorders();
            $runningState = (isset($allRunning[$cameraId])) ? 1 : 0;
            $result = array('error' => 0, 'running' => $runningState);
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return($result);
    }

}
