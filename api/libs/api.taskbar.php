<?php

/**
 * Taskbar loading and rendering class
 */
class Taskbar {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains currently loaded categories as dir=>name
     *
     * @var array
     */
    protected $categories = array();

    /**
     * Contains current run alerts if available
     *
     * @var string
     */
    protected $currentAlerts = '';

    /**
     * Contains full list of loaded taskbar elements
     *
     * @var array
     */
    protected $loadedElements = array();

    /**
     * Taskbar elements rendered content
     *
     * @var string
     */
    protected $taskbarContent = '';

    /**
     * Contains default icon size
     *
     * @var int
     */
    protected $iconSize = 128;

    /**
     * Contains default taskbar elements path
     */
    const BASE_PATH = 'config/taskbar.d/';

    /**
     * Contains path to widgets code
     */
    const WIDGETS_CODEPATH = 'config/taskbar.d/widgets/';

    /**
     * Contains default module URL
     */
    const URL_ME = '?module=taskbar';

    /**
     * Creates new taskbar instance
     */
    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
        $this->setCategories();
    }

    /**
     * Loads system alter config into protected property
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
     * Inits system message helper object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets available taskbar element categories
     * 
     * @return void
     */
    protected function setCategories() {
        $this->categories['widgets'] = '';
        $this->categories['playback'] = __('Playback');
        $this->categories['settings'] = __('Settings');
        $this->categories['system'] = __('System');
    }

    /**
     * Sets current administrators login into protected prof for further usage
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Renders taskbar icon element
     * 
     * @param string $url
     * @param string $name
     * @param string $icon
     * 
     * @return string
     */
    protected function renderIconElement($url, $elementName, $elementIcon) {
        $result = '';
        $name = __($elementName);
        $icon = 'skins/taskbar/' . $elementIcon;
        $result = '<div class="dashtask" style="height:' . ($this->iconSize + 38) . 'px; width:' . ($this->iconSize + 38) . 'px;"> <a href="' . $url . '"><img  src="' . $icon . '" border="0" width="' . $this->iconSize . '"  height="' . $this->iconSize . '" alt="' . $name . '" title="' . $name . '"></a> <br><br>' . $name . ' </div>';
        return ($result);
    }

    /**
     * Checks element required rights, options and returns element content
     * 
     * @param array $elementData
     * 
     * @return string
     */
    protected function buildElement($elementData) {
        $result = '';
        $elementId = (isset($elementData['ID'])) ? $elementData['ID'] : '';
        $elementType = (!empty($elementData['TYPE'])) ? $elementData['TYPE'] : '';
        //basic taskbar icon
        if ($elementType == 'icon') {
            $accesCheck = false;
            $elementRight = (!empty($elementData['NEED_RIGHT'])) ? $elementData['NEED_RIGHT'] : '';
            if (!empty($elementRight)) {
                if (cfr($elementRight)) {
                    $accesCheck = true;
                }
            } else {
                $accesCheck = true;
            }
            //basic rights check
            if ($accesCheck) {
                $elementOption = (!empty($elementData['NEED_OPTION'])) ? $elementData['NEED_OPTION'] : '';
                $optionCheck = false;
                if (!empty($elementOption)) {
                    if (isset($this->altCfg[$elementOption])) {
                        if ($this->altCfg[$elementOption]) {
                            $optionCheck = true;
                        }
                    } else {
                        if (!isset($elementData['UNIMPORTANT'])) {
                            $this->currentAlerts .= $this->messages->getStyledMessage(__('Missed config option') . ': ' . $elementOption . ' ' . __('required by') . ' ' . $elementId, 'error');
                        }
                    }
                } else {
                    $optionCheck = true;
                }

                if ($optionCheck) {
                    $elementName = (!empty($elementData['NAME'])) ? $elementData['NAME'] : '';
                    $elementUrl = (!empty($elementData['URL'])) ? $elementData['URL'] : '';
                    $elementIcon = (!empty($elementData['ICON'])) ? $elementData['ICON'] : '';
                    $result .= $this->renderIconElement($elementUrl, $elementName, $elementIcon);
                }
            }
        }

        //widgets loading
        if ($elementType == 'widget') {
            $accesCheck = false;
            $elementRight = (!empty($elementData['NEED_RIGHT'])) ? $elementData['NEED_RIGHT'] : '';
            if (!empty($elementRight)) {
                if (cfr($elementRight)) {
                    $accesCheck = true;
                }
            } else {
                $accesCheck = true;
            }
            //basic rights check
            if ($accesCheck) {
                $elementOption = (!empty($elementData['NEED_OPTION'])) ? $elementData['NEED_OPTION'] : '';
                $optionCheck = false;
                if (!empty($elementOption)) {
                    if (isset($this->altCfg[$elementOption])) {
                        if ($this->altCfg[$elementOption]) {
                            $optionCheck = true;
                        }
                    } else {
                        if (!isset($elementData['UNIMPORTANT'])) {
                            $this->currentAlerts .= $this->messages->getStyledMessage(__('Missed config option') . ': ' . $elementOption . ' ' . __('required by') . ' ' . $elementId, 'error');
                        }
                    }
                } else {
                    $optionCheck = true;
                }


                if ($optionCheck) {
                    //run widget code
                    if (isset($elementData['CODEFILE'])) {
                        if (file_exists(self::WIDGETS_CODEPATH . $elementData['CODEFILE'])) {
                            require_once (self::WIDGETS_CODEPATH . $elementData['CODEFILE']);
                            if (class_exists($elementData['ID'])) {
                                $widget = new $elementData['ID']();
                                $result .= $widget->render();
                            } else {
                                $this->currentAlerts .= $this->messages->getStyledMessage(__('Widget class not exists') . ': ' . $elementData['ID'], 'error');
                            }
                        } else {
                            $this->currentAlerts .= $this->messages->getStyledMessage(__('File not exist') . ': ' . self::WIDGETS_CODEPATH . $elementData['CODEFILE'], 'warning');
                        }
                    } else {
                        $this->currentAlerts .= $this->messages->getStyledMessage(__('Wrong element format') . ': ' . $elementData['ID'], 'warning');
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Loads and returns category taskbar elements
     * 
     * @param string $category
     * 
     * @return string
     */
    protected function loadCategoryElements($category) {
        $result = '';
        $elementsPath = self::BASE_PATH . $category . '/';
        $allElements = rcms_scandir($elementsPath, '*.ini');
        $categoryContent = '';
        if (!empty($allElements)) {
            $categoryName = (isset($this->categories[$category])) ? $this->categories[$category] : '';
            foreach ($allElements as $io => $eachfilename) {
                $elementData = parse_ini_file($elementsPath . $eachfilename);
                if ((isset($elementData['TYPE'])) AND ( isset($elementData['ID']))) {
                    if (!isset($this->loadedElements[$elementData['ID']])) {
                        $this->loadedElements[$elementData['ID']] = $elementData;
                        $categoryContent .= $this->buildElement($elementData);
                    } else {
                        $this->currentAlerts .= $this->messages->getStyledMessage(__('Duplicate element ID') . ': ' . $elementData['ID'] . ' -> ' . $eachfilename, 'warning');
                    }
                } else {
                    $this->currentAlerts .= $this->messages->getStyledMessage(__('Wrong element format') . ': ' . $eachfilename, 'warning');
                }
            }

            //injecting optional ReportMaster reports here
            if ($category == 'reports') {
                if (@$this->altCfg['TB_REPORTMASTER']) {
                    $reportMaster = new ReportMaster();
                    $availableReports = $reportMaster->getTaskBarReports();
                    if (!empty($availableReports)) {
                        foreach ($availableReports as $eachReportId => $eachReportElement) {
                            $categoryContent .= $this->buildElement($eachReportElement);
                        }
                    }
                }
            }

            if (!empty($categoryContent)) {
                $result .= wf_tag('p') . wf_tag('h3') . wf_tag('u') . $categoryName . wf_tag('u', true) . wf_tag('h3', true) . wf_tag('p', true);
                $result .= wf_tag('div', false, 'dashboard');
                $result .= $categoryContent;
                $result .= wf_tag('div', true);
                $result .= wf_CleanDiv();
            }
        }
        return ($result);
    }

    /**
     * Loads and try to render all of available taskbar categories
     * 
     * @return string
     */
    protected function loadAllCategories() {
        $result = '';
        if (!empty($this->categories)) {
            foreach ($this->categories as $category => $categoryname) {
                $result .= $this->loadCategoryElements($category);
            }
        }
        return ($result);
    }

    /**
     * Checks for default password usage, etc.
     * 
     * @return void
     */
    protected function checkSecurity() {
        if (isset($_COOKIE['yalf_user'])) {
            if ($_COOKIE['yalf_user'] == 'admin:fe01ce2a7fbac8fafaed7c982a04e229') {
                if (!file_exists('DEMO_MODE') AND !file_exists('exports/FIRST_INSTALL')) {
                    $notice = __('You are using the default login and password') . '. ' . __('Dont do this') . '.';
                    // ugly hack to prevent elements autofocusing
                    $label = wf_TextInput('dontfocusonlinks', '', '', false, '', '', '', '', 'style="width: 0; height: 0; top: -100px; position: absolute;"');
                    $label .= wf_tag('div', false, '', 'style="min-width:550px;"') . $this->messages->getStyledMessage($notice, 'error') . wf_tag('div', true);
                    $label .= wf_tag('br');
                    $label .= wf_tag('center') . wf_img_sized('skins/securitywolf.png', '', '', '300') . wf_tag('center' . true);
                    $label .= wf_delimiter(1);
                    $label .= wf_Link('?module=usermanager&edituserdata=admin', __('Change admin user password'), true, 'confirmagree');
                    $this->currentAlerts .= wf_modalOpenedAuto(__('Danger') . '!', $label);
                }
            }
        }
    }

    /**
     * Returns rendered taskbar elements and services content
     * 
     * @return string
     */
    public function renderTaskbar() {
        $result = '';
        $this->taskbarContent = $this->loadAllCategories();
        $this->checkSecurity();
        if (!empty($this->currentAlerts)) {
            $result .= $this->currentAlerts;
        }
        $result .= $this->taskbarContent;
        return ($result);
    }
}

/**
 * Basic taskbar widgets class.
 */
class TaskbarWidget {

    /**
     * Creates new instance of taskbar widget
     */
    public function __construct() {
        
    }

    /**
     * Returns content in default taskbar dashtask coontainer
     * 
     * @param string $content
     * @param string $options
     * 
     * @return string
     */
    protected function widgetContainer($content, $options = '') {
        $result = wf_tag('div', false, 'dashtask', $options);
        $result .= $content;
        $result .= wf_tag('div', true);
        return ($result);
    }

    /**
     * Returns result that directly embeds into taskbar
     * 
     * @return string
     */
    public function render() {
        $result = 'EMPTY_WIDGET';
        return ($result);
    }
}
