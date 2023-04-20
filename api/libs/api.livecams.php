<?php

/**
 * Live cams implementation
 */
class LiveCams {

    /**
     * Chanshots instance placeholder
     *
     * @var object
     */
    protected $chanshots = '';

    /**
     * Cameras instance placeholder
     *
     * @var  object
     */
    protected $cameras = '';

    /**
     * Contains all available cameras data as id=>camFullData
     *
     * @var array
     */
    protected $allCamerasData = array();

    /**
     * Contains system messages helper instance
     *
     * @var object
     */
    protected $messages = '';

    public function __construct() {
        $this->initMessages();
        $this->initCameras();
        $this->initChanshots();
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
     * Inits chanshots instance for further usage
     * 
     * @return void
     */
    protected function initChanshots() {
        $this->chanshots = new ChanShots();
    }

    /**
     * Inits cameras instance and loads camera full data
     * 
     * @return void
     */
    protected function initCameras() {
        $this->cameras = new Cameras();
        $this->allCamerasData = $this->cameras->getAllCamerasFullData();
    }

    /**
     * Lists available cameras
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        if (!empty($this->allCamerasData)) {
            $style = 'style="float: left; margin: 5px;"';
            $result .= wf_tag('div');
            foreach ($this->allCamerasData as $eachCameraId => $eachCameraData) {
                $cameraChannel = $eachCameraData['CAMERA']['channel'];
                $channelScreenshot = $this->chanshots->getChannelScreenShot($cameraChannel);
                $cameraLabel = $this->cameras->getCameraComment($cameraChannel);
                if (empty($channelScreenshot)) {
                    $channelScreenshot = 'skins/noimage.jpg';
                }
                $result .= wf_tag('div', false, '', $style);
                $result .= wf_img($channelScreenshot, $cameraLabel, 'height: 480px; height: 270px;  object-fit: cover;');
                $result .= wf_tag('div', true);
            }
            $result .= wf_tag('div', true);
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

}
