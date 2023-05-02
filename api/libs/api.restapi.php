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
            ),
            'system' => array(
                'gethealth' => 'systemGetHealth'
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

    ///////////////////////////
    // System object methods //
    ///////////////////////////

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
        }
        $result['uptime'] = $uptime;
        //system load
        $loadAvg = sys_getloadavg();
        $result['loadavg'] = round($loadAvg[0], 2);
        return($result);
    }

}
