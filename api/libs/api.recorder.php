<?php

class Recorder {

    protected $binPaths = array();

    /**
     * Cameras instance placeholder
     *
     * @var object
     */
    protected $cameras = '';

    /**
     * Contains stardust process manager instance
     *
     * @var object
     */
    protected $stardust = '';

    /**
     * Contains ffmpeg binary path
     *
     * @var string
     */
    protected $ffmpgPath = '';

    /**
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    public function __construct() {
        $this->initMessages();
        $this->loadConfigs();
        $this->initStardust();
        $this->initCameras();
    }

    protected function loadConfigs() {
        global $ubillingConfig;
        $this->binPaths = $ubillingConfig->getBinpaths();
        $this->ffmpgPath = $this->binPaths['FFMPG_PATH'];
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
    protected function initCameras() {
        $this->cameras = new Cameras();
    }

    /**
     * Inits stardust process manager
     * 
     * @return void
     */
    protected function initStardust() {
        $this->stardust = new StarDust();
    }

}
