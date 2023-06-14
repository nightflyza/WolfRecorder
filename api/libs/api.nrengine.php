<?php

class NREngine {

    /**
     * HTTP API abstraction layer placeholder
     *
     * @var object
     */
    protected $api = '';

    /**
     * Contains alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains base recognition service URL
     *
     * @var string
     */
    protected $baseApiUrl = '';

    /**
     * Contains recognition service engine
     *
     * @var string
     */
    protected $engine = 'pytorch';

    /**
     * Object recognition confidence 
     *
     * @var int
     */
    protected $confidence = 50;

    /**
     * some predefined stuff
     */
    const ROUTE_DETECT = '/detect';
    const ROUTE_IMAGE = '/image';
    const ROUTE_STREAM = '/stream';

    public function __construct() {
        $this->loadConfigs();
        $this->initApi();
    }

    /**
     * Loads some required configs
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits omae http abstraction layer
     * 
     * @return void
     */
    protected function initApi() {
        if ($this->altCfg['NEURAL_ENABLED'] AND $this->altCfg['NEURAL_API_URL']) {
            $this->api = new OmaeUrl();
            $this->baseApiUrl = $this->altCfg['NEURAL_API_URL'];
        }
    }

    /**
     * Makes request body as JSON
     * 
     * @param string $url
     * 
     * @return string
     */
    protected function getRequest($url) {
        $result = array();
        $result['id'] = 'wr';
        $result['detector_name'] = $this->engine;
        $result['preprocess'] = array();
        $result['detect'] = array('*' => $this->confidence);
        $regions = array();
        $regions[] = array(
            'top' => 0.1,
            'left' => 0.1,
            'bottom' => 0.9,
            'right' => 0.9,
            'detect' => array('*' => $this->confidence),
            'covers' => false
        );

        $result = array(
            'regions' => $regions,
            'data' => $url
        );
        $result = json_encode($result);
        return($result);
    }

    /**
     * Performs recognition request for some endpoint
     * 
     * @param string $url
     * @param string $endpoint
     * 
     * @return string
     */
    protected function requestDetection($url, $endpoint) {
        $requestJson = $this->getRequest($url);
        $this->api->dataPostRaw($requestJson);
        $this->api->dataHeader('Content-Type', 'application/json;charset=UTF-8');
        $requestUrl = $this->baseApiUrl . $endpoint;
        $result = $this->api->response($requestUrl);
        return($result);
    }

    /**
     * Performs recognition request for video stream
     * 
     * @param string $url
     * 
     * @return string
     */
    protected function requestStreamDetection($url) {
        $requestJson = $this->getRequest($url);
        //$this->api->dataHeader('Content-Type', 'application/json;charset=UTF-8');
        $requestUrl = $this->baseApiUrl . self::ROUTE_STREAM . '?detect_request=' . urlencode($requestJson);
        deb($requestUrl);
        $result = $this->api->response($requestUrl);
        return($result);
    }

    /**
     * Returns marked objects as JPEG image body
     * 
     * @param string $url
     * 
     * @return string
     */
    public function detectImage($url) {
        $result = $this->requestDetection($url, self::ROUTE_IMAGE);
        return($result);
    }

    /**
     * Returns detected objects as array
     * 
     * @param string $url
     * 
     * @return array
     */
    public function detectObjects($url) {
        $result = array();
        $result = $this->requestDetection($url, self::ROUTE_DETECT);
        if (!empty($result)) {
            $result = json_decode($result, true);
        }
        return($result);
    }

    public function detectStream($url) {
        $result = $this->requestStreamDetection($url);
        return($result);
    }

}
