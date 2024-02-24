<?php

class ConfigForge {

    /**
     * Contains current config lines as index=>line
     *
     * @var array
     */
    protected $currentConfig = array();

    public function __construct() {
    }

    public function loadConfig($configPath) {
        if (is_readable($configPath)) {
            $configTmp = file_get_contents($configPath);
            if (!empty($configTmp)) {
                $this->currentConfig = explodeRows($configTmp);
            }
        }
    }

    public function renderEditor($config, $spec) {
        $result='';
        return($result);
    }
}

/**
 * 
 * $forge->renderEditor('specfile.conf');
 * 
 * [section]
 * CONFIG="config/alter.ini"
 * OPTION=ROTATOR_FAST
 * TYPE=TRIGGER
 * VALUES="1,0"
 * VALIDATOR=""
 * 
 *   
 */
