<?php

/**
 * Client REST API for user applications
 */
class ClientRestAPI {

    /**
     * Contains available objects and their methods callbacks as object=>callback=>methodName
     *
     * @var array
     */
    protected $objects = array();

    /**
     * Cameras instance placeholder
     *
     * @var object
     */
    protected $cameras = '';

    /**
     * ACL instance placeholder
     *
     * @var object
     */
    protected $acl = '';

    /**
     * UserManager instance placeholder
     *
     * @var object
     */
    protected $userManager = '';

    /**
     * Recorder instance placeholder
     *
     * @var object
     */
    protected $recorder = '';

    /**
     * LiveCams instance placeholder
     *
     * @var object
     */
    protected $liveCams = '';

    /**
     * ChanShots instance placeholder
     *
     * @var object
     */
    protected $chanshots = '';

    /**
     * Contains all cameras full data as cameraId=>camFullData
     *
     * @var array
     */
    protected $allCamerasData = array();

    /**
     * Contains running recorders as cameraId=>pid
     *
     * @var array
     */
    protected $runningRecorders = array();

    /**
     * Contains running main live streams as cameraId=>pid
     *
     * @var array
     */
    protected $runningMainStreams = array();

    /**
     * Contains running sub live streams as cameraId=>pid
     *
     * @var array
     */
    protected $runningSubStreams = array();

    /**
     * Is channels runtime data loaded flag
     *
     * @var bool
     */
    protected $channelsRuntimeLoaded = false;

    /**
     * POST field name for JSON request body
     */
    const PROUTE_DATA = 'data';

    /**
     * Optional GET parameter names for auth (browser debugging)
     */
    const GROUTE_LOGIN = 'login';
    const GROUTE_PASSWORD = 'password';
    const GROUTE_AUTHTOKEN = 'authtoken';

    /**
     * User right required for client REST API access
     */
    const RIGHT_WALL = 'WALL';

    /**
     * Creates new client REST API instance
     */
    public function __construct() {
        $this->setAvailableObjects();
    }

    /**
     * Returns whether LIVE_WALL alter option is enabled
     *
     * @return bool
     */
    protected function isLiveWallEnabled() {
        $result = false;
        global $ubillingConfig;
        if ($ubillingConfig->getAlterParam(LiveCams::OPTION_WALL)) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Returns whether user login has WALL right
     *
     * @param string $login
     *
     * @return bool
     */
    protected function userHasWallRight($login) {
        $result = false;
        global $system;
        if ($system->checkForRight(self::RIGHT_WALL, $login)) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Sets available objects and their methods
     *
     * @return void
     */
    protected function setAvailableObjects() {
        $this->objects = array(
            'channels' => array(
                'getall' => 'channelsGetAll'
            ),
        );
    }

    /**
     * Inits cameras instance for further usage
     *
     * @return void
     */
    protected function initCameras() {
        if (empty($this->cameras)) {
            $this->cameras = new Cameras();
        }
    }

    /**
     * Inits ACL instance for further usage
     *
     * @return void
     */
    protected function initAcl() {
        if (empty($this->acl)) {
            $this->acl = new ACL();
        }
    }

    /**
     * Inits UserManager instance for further usage
     *
     * @return void
     */
    protected function initUserManager() {
        if (empty($this->userManager)) {
            $this->userManager = new UserManager();
        }
    }

    /**
     * Inits recorder instance for further usage
     *
     * @return void
     */
    protected function initRecorder() {
        if (empty($this->recorder)) {
            $this->recorder = new Recorder();
        }
    }

    /**
     * Inits LiveCams instance for further usage
     *
     * @return void
     */
    protected function initLiveCams() {
        if (empty($this->liveCams)) {
            $this->liveCams = new LiveCams();
        }
    }

    /**
     * Inits ChanShots instance for further usage
     *
     * @return void
     */
    protected function initChanshots() {
        if (empty($this->chanshots)) {
            $this->chanshots = new ChanShots();
        }
    }

    /**
     * Preloads cameras, recorders and live streams data for channels listing
     *
     * @return void
     */
    protected function loadChannelsRuntime() {
        if (!$this->channelsRuntimeLoaded) {
            $this->initCameras();
            $this->allCamerasData = $this->cameras->getAllCamerasFullData();
            $this->initRecorder();
            $this->runningRecorders = $this->recorder->getRunningRecorders();
            $this->initLiveCams();
            $this->runningMainStreams = $this->liveCams->getRunningStreams();
            $this->runningSubStreams = $this->liveCams->getRunningSubStreams();
            $this->initChanshots();
            $this->channelsRuntimeLoaded = true;
        }
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
        return ($result);
    }

    /**
     * Collects request input from optional GET auth params and POST JSON body
     *
     * @return array
     */
    protected function collectInputData() {
        $result = array();
        if (ubRouting::checkGet(self::GROUTE_LOGIN)) {
            $result[self::GROUTE_LOGIN] = ubRouting::get(self::GROUTE_LOGIN, 'login');
        }
        if (ubRouting::checkGet(self::GROUTE_PASSWORD)) {
            $result[self::GROUTE_PASSWORD] = ubRouting::get(self::GROUTE_PASSWORD, 'raw');
        }
        if (ubRouting::checkGet(self::GROUTE_AUTHTOKEN)) {
            $result[self::GROUTE_AUTHTOKEN] = ubRouting::get(self::GROUTE_AUTHTOKEN, 'raw');
        }
        if (ubRouting::checkPost(self::PROUTE_DATA)) {
            $unpackData = json_decode(ubRouting::post(self::PROUTE_DATA), true);
            if (is_array($unpackData)) {
                foreach ($unpackData as $eachKey => $eachValue) {
                    $result[$eachKey] = $eachValue;
                }
            }
        }
        return ($result);
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
        if ($this->isLiveWallEnabled()) {
            if (!empty($this->objects)) {
                foreach ($this->objects as $eachObject => $objectMethods) {
                    if (ubRouting::checkGet($eachObject)) {
                        $methodCallback = ubRouting::get($eachObject,'safe');
                        if (isset($objectMethods[$methodCallback])) {
                            $methodName = $objectMethods[$methodCallback];
                            if (method_exists($this, $methodName)) {
                                $inputData = $this->collectInputData();
                                $result = $this->$methodName($inputData);
                            } else {
                                $result = array(
                                    'error' => 2,
                                    'message' => __('Method not exists')
                                );
                            }
                        } else {
                            $result = array(
                                'error' => 2,
                                'message' => __('Method not exists')
                            );
                        }
                    }
                }
            }
        } else {
            $result = array(
                'error' => 8,
                'message' => __('Live wall') . ' ' . __('Disabled')
            );
        }
        $this->renderReply($result);
    }

    /**
     * Returns request reply as JSON
     *
     * @param array $data
     *
     * @return void
     */
    protected function renderReply($data) {
        header('Content-Type: application/json; charset=UTF-8');
        $data = json_encode($data);
        die($data);
    }

    /**
     * Authenticates user by login+password or login+authtoken
     *
     * @param array $request
     *
     * @return array
     */
    protected function authenticateUser($request) {
        $result = array('error' => 6, 'message' => __('Wrong credentials'));
        $hasPassword = (isset($request['password']) and strlen($request['password']) > 0);
        $hasAuthtoken = (isset($request['authtoken']) and strlen($request['authtoken']) > 0);
        if ($hasPassword or $hasAuthtoken) {
            $login = $request['login'];
            $this->initUserManager();
            $allUsersData = $this->userManager->getAllUsersData();
            if (isset($allUsersData[$login])) {
                $userData = $allUsersData[$login];
                if (!empty($userData)) {
                    $authenticated = false;
                    if ($hasPassword) {
                        $passwordHash = md5($request['password']);
                        if ($passwordHash == $userData['password']) {
                            $authenticated = true;
                        }
                    } else {
                        $expectedToken = md5($login . $userData['password']);
                        if ($request['authtoken'] == $expectedToken) {
                            $authenticated = true;
                        }
                    }
                    if ($authenticated) {
                        $result = array('error' => 0, 'login' => $login);
                    }
                } else {
                    $result = array('error' => 7, 'message' => __('Error reading user profile'));
                }
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return ($result);
    }

    /**
     * Returns channelId=>cameraId map accessible by some user login
     *
     * @param string $login
     *
     * @return array
     */
    protected function getAccessibleChannels($login) {
        $result = array();
        global $system;
        $allChannelsAccess = false;
        if ($system->checkForRight('OPERATOR', $login)) {
            $allChannelsAccess = true;
        } else {
            if ($system->checkForRight('ROOT', $login)) {
                $allChannelsAccess = true;
            }
        }
        if ($allChannelsAccess) {
            $this->initCameras();
            $result = $this->cameras->getAllCamerasChannels();
        } else {
            $this->initAcl();
            $allChannelAcls = $this->acl->getAllChannelAclsData();
            if (isset($allChannelAcls[$login])) {
                $result = $allChannelAcls[$login];
            }
        }
        return ($result);
    }

    /**
     * Resolves channel screenshot path with pseudo-shots on error states
     *
     * @param string $channelId
     * @param bool $cameraActive
     *
     * @return string
     */
    protected function resolveChannelScreenshot($channelId, $cameraActive) {
        $result = '';
        $channelScreenshot = $this->chanshots->getChannelScreenShot($channelId);
        if (empty($channelScreenshot)) {
            $result = ChanShots::ERR_NOSIG;
        } else {
            $chanshotValid = $this->chanshots->isChannelScreenshotValid($channelScreenshot);
            if (!$chanshotValid) {
                $result = ChanShots::ERR_CORRUPT;
            } else {
                $result = $channelScreenshot;
            }
        }
        if (!$cameraActive) {
            $result = ChanShots::ERR_DISABLD;
        }
        return ($result);
    }

    /**
     * Returns process running state as 0/1
     *
     * @param array $runningMap
     * @param int $cameraId
     *
     * @return int
     */
    protected function getRunningState($runningMap, $cameraId) {
        $result = 0;
        if (isset($runningMap[$cameraId])) {
            $result = 1;
        }
        return ($result);
    }

    /**
     * Returns all channels available for authenticated user
     *
     * @param array $request
     *
     * @return array
     */
    protected function channelsGetAll($request) {
        $result = array();
        $requiredFields = array('login');
        if ($this->checkRequestFields($requiredFields, $request)) {
            $authResult = $this->authenticateUser($request);
            if ($authResult['error'] == 0) {
                $login = $authResult['login'];
                if ($this->userHasWallRight($login)) {
                    $userChannels = $this->getAccessibleChannels($login);
                    $channels = array();
                    if (!empty($userChannels)) {
                        $this->loadChannelsRuntime();
                        foreach ($userChannels as $eachChannelId => $eachCameraId) {
                            $cameraActive = false;
                            if (isset($this->allCamerasData[$eachCameraId])) {
                                if (!empty($this->allCamerasData[$eachCameraId]['CAMERA']['active'])) {
                                    $cameraActive = true;
                                }
                            }
                            $activeState = 0;
                            if ($cameraActive) {
                                $activeState = 1;
                            }
                            $channelComment = $this->cameras->getCameraCommentById($eachCameraId);
                            $channels[] = array(
                                'id' => $eachChannelId,
                                'comment' => $channelComment,
                                'active' => $activeState,
                                'recording' => $this->getRunningState($this->runningRecorders, $eachCameraId),
                                'mainstream' => $this->getRunningState($this->runningMainStreams, $eachCameraId),
                                'substream' => $this->getRunningState($this->runningSubStreams, $eachCameraId),
                                'screenshot' => $this->resolveChannelScreenshot($eachChannelId, $cameraActive)
                            );
                        }
                    }
                    $result = array('error' => 0, 'channels' => $channels);
                } else {
                    $result = array(
                        'error' => 9,
                        'message' => __('Live wall access denied')
                    );
                }
            } else {
                $result = $authResult;
            }
        } else {
            $result = array('error' => 3, 'message' => __('Wrong request data'));
        }
        return ($result);
    }

}
