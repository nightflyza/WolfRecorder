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
            'models' => array('getall' => 'modelsGetAll')
        );
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
                                $inputData = json_decode(ubRouting::post(self::PROUTE_DATA, true));
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

}
