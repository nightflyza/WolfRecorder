<?php

/**
 * Live streams camera keep alive events management
 */
class StreamDog {

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Predefined stuff here
     */
    const TIMEOUT = 60;
    const SUB_TIMEOUT=600;
    const CACHE_KEY = 'KEEPALIVE_';
    const CACHE_SUB = 'KEEPALIVE_';
    const ROUTE_KEEPALIVE = 'keepstreamalive';
    const ROUTE_KEEPSUBALIVE = 'keepsubalive';

    public function __construct() {
        $this->initCache();
    }

    /**
     * Inits caching object instance for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Sets camera as alive
     * 
     * @param int $cameraId
     * 
     * @return void
     */
    public function keepAlive($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        $cacheKey = self::CACHE_KEY . $cameraId;
        $cachedData = $this->cache->get($cacheKey, self::TIMEOUT);
        if (empty($cachedData)) {
            $cachedData = time();
        }
        $this->cache->set($cacheKey, $cachedData, self::TIMEOUT);
    }

    /**
     * Sets camera sub-stream as alive
     * 
     * @param int $cameraId
     * 
     * @return void
     */
    public function keepSubAlive($cameraId) {
        $cameraId = ubRouting::filters($cameraId, 'int');
        $cacheKey = self::CACHE_SUB . $cameraId;
        $cachedData = $this->cache->get($cacheKey, self::SUB_TIMEOUT);
        if (empty($cachedData)) {
            $cachedData = time();
        }
        $this->cache->set($cacheKey, $cachedData, self::SUB_TIMEOUT);
    }

    /**
     * Checks is camera being watched by someone?
     * 
     * @param int $cameraId
     * 
     * @return bool
     */
    public function isCameraInUse($cameraId) {
        $result = false;
        $cameraId = ubRouting::filters($cameraId, 'int');
        $cacheKey = self::CACHE_KEY . $cameraId;
        $cachedData = $this->cache->get($cacheKey, self::TIMEOUT);
        if (!empty($cachedData)) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Checks is camera low quality stream being watched by someone?
     * 
     * @param int $cameraId
     * 
     * @return bool
     */
    public function isCameraSubInUse($cameraId) {
        $result = false;
        $cameraId = ubRouting::filters($cameraId, 'int');
        $cacheKey = self::CACHE_SUB . $cameraId;
        $cachedData = $this->cache->get($cacheKey, self::SUB_TIMEOUT);
        if (!empty($cachedData)) {
            $result = true;
        }
        return ($result);
    }

    /**
     * Returns keep-alive JS code
     * 
     * @param string $url
     * @param inst $timeout
     * 
     * @return string
     */
    public function getKeepAliveCallback($url, $timeout) {
        $result = '';
        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= '
        function keepAliveRequest () {
         $.ajax({
                type: "GET",
                url: "' . $url . '",
                cache: false
            });
        }
        
        var timer = setInterval(keepAliveRequest, ' . $timeout . ');
        ';
        $result .= wf_tag('script', true);
        return ($result);
    }
}
