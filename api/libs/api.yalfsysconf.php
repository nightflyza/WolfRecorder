<?php

/**
 * System configuration editor class
 */
class YalfSysConf {

    /**
     * Contains configs array editable from web as filePath=>just name
     *
     * @var array
     */
    protected $editableConfigs = array();

    /**
     * Message helper system object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some URLs, routes.. etc
     */
    const URL_ME = '?module=sysconf';
    const ROUTE_EDIT = 'editconfig';
    const ROUTE_PHPINFO = 'phpinfo';
    const PROUTE_FILEPATH = 'editfilepath';
    const PROUTE_FILECONTENT = 'editfilecontent';

    public function __construct() {
        $this->initMessages();
        $this->loadEditableConfigs();
    }

    /**
     * Inits system messages helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads config files editable from web
     * 
     * @return void
     */
    protected function loadEditableConfigs() {
        global $system;
        $rawConf = parse_ini_file($system::YALF_CONF_PATH);
        if (!empty($rawConf)) {
            if (isset($rawConf['YALF_EDITABLE_CONFIGS'])) {
                if (!empty($rawConf['YALF_EDITABLE_CONFIGS'])) {
                    $rawOption = explode(',', $rawConf['YALF_EDITABLE_CONFIGS']);
                    if (!empty($rawOption)) {
                        foreach ($rawOption as $index => $eachConfig) {
                            $this->editableConfigs[$eachConfig] = basename($eachConfig);
                        }
                    }
                }
            }
        }
    }

    /**
     * Renders list of available and required PHP extensions
     * 
     * @return string
     */
    protected function checkPHPExtensions() {
        $result = '';
        $result = '';
        if (file_exists(CONFIG_PATH . 'optsextcfg')) {
            $allRequired = file_get_contents(CONFIG_PATH . 'optsextcfg');
            if (!empty($allRequired)) {
                $allRequired = explodeRows($allRequired);
                if (!empty($allRequired)) {
                    foreach ($allRequired as $io => $each) {
                        if (!empty($each)) {
                            $each = trim($each);
                            $notice = '';
                            if (!extension_loaded($each)) {
                                switch ($each) {
                                    case 'mysql':
                                        $notice = ' ' . __('Deprecated in') . '  PHP 7.0';
                                        break;
                                    case 'ereg':
                                        $notice = ' ' . __('Deprecated in') . '  PHP 7.0';
                                        break;
                                    case 'memcache':
                                        $notice = ' ' . __('Deprecated in') . '  PHP 7.0';
                                        break;
                                    case 'xhprof':
                                        $notice = ' ' . __('May require manual installation');
                                        break;
                                }
                                $result .= wf_tag('span', false, 'alert_error') . __('PHP extension not found') . ': ' . $each . $notice . wf_tag('span', true);
                            } else {
                                $result .= wf_tag('span', false, 'alert_success') . __('PHP extension loaded') . ': ' . $each . wf_tag('span', true);
                            }
                        }
                    }
                }
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exeption') . ': OPTSEXTCFG_NOT_FOUND', 'error');
        }
        return($result);
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        if (!empty($this->editableConfigs)) {
            foreach ($this->editableConfigs as $eachPath => $eachName) {
                $encPath = base64_encode($eachPath);
                $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $encPath, web_edit_icon() . ' ' . $eachName, false, 'ubButton');
            }
        }

        $sysInfoData = '';
        $phpInfoCode = wf_modal(wf_img('skins/icon_puzzle.png') . ' ' . __('Check required PHP extensions'), __('Check required PHP extensions'), $this->checkPHPExtensions(), 'ubButton', '800', '600');
        $phpInfoCode .= wf_tag('br');
        $phpInfoCode .= wf_tag('iframe', false, '', 'src="' . self::URL_ME . '&' . self::ROUTE_PHPINFO . '=true" width="1000" height="500" frameborder="0"') . wf_tag('iframe', true);
        $result .= wf_modalAuto(wf_img('skins/icon_php.png') . ' ' . __('Information about PHP version'), __('Information about PHP version'), $phpInfoCode, 'ubButton');

        return($result);
    }

    /**
     * Returns simple text editing form
     * 
     * @param string $path
     * @param string $content
     * 
     * @return string
     */
    protected function fileEditorForm($path, $content) {
        $result = '';
        $content = htmlentities($content, ENT_COMPAT, "UTF-8");

        $inputs = wf_HiddenInput(self::PROUTE_FILEPATH, $path);
        $inputs .= wf_tag('textarea', false, 'fileeditorarea', 'name="' . self::PROUTE_FILECONTENT . '" cols="145" rows="30"');
        $inputs .= $content;
        $inputs .= wf_tag('textarea', true);
        $inputs .= wf_tag('br');
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_ME);
        return ($result);
    }

    /**
     * Catches editing request and render edit area if required
     * 
     * @return string
     */
    public function renderFileEditor() {
        $result = '';
        if (ubRouting::checkGet(self::ROUTE_EDIT)) {
            $fileToEdit = base64_decode(ubRouting::get(self::ROUTE_EDIT));
            if (file_exists($fileToEdit)) {
                if (is_readable($fileToEdit)) {
                    if (is_writable($fileToEdit)) {
                        $fileContent = file_get_contents($fileToEdit);
                        $result .= $this->fileEditorForm($fileToEdit, $fileContent);
                    } else {
                        $result .= $this->messages->getStyledMessage(__('File is not writable') . ': ' . $fileToEdit, 'error');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Cant read file') . ': ' . $fileToEdit, 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('File not exists') . ': ' . $fileToEdit, 'error');
            }
        }
        return($result);
    }

    /**
     * Saves editing file if its exists/readable/writable on receiving expected POST variables
     * 
     * @return void/string on error
     */
    public function saveFile() {
        $result = '';
        if (ubRouting::checkPost(array(self::PROUTE_FILECONTENT, self::PROUTE_FILEPATH))) {
            $fileToEdit = ubRouting::post(self::PROUTE_FILEPATH);
            if (file_exists($fileToEdit)) {
                if (is_readable($fileToEdit)) {
                    if (is_writable($fileToEdit)) {
                        $fileContent = ubRouting::post(self::PROUTE_FILECONTENT);
                        if (ispos($fileContent, "\r\n")) {
                            //cleanup to unix EOL
                            $fileContent = str_replace("\r\n", "\n", $fileContent);
                        }
                        file_put_contents($fileToEdit, $fileContent);
                        log_register('SYSCONF UPDATE FILE `' . $fileToEdit . '`');
                    } else {
                        $result .= __('File is not writable') . ': ' . $fileToEdit;
                    }
                } else {
                    $result .= __('Cant read file') . ': ' . $fileToEdit;
                }
            } else {
                $result .= __('File not exists') . ': ' . $fileToEdit;
            }
        }
        return($result);
    }

}
