<?php

/**
 * ONVIF-explorer and custom device templates manager implementation
 */
class ModelCraft {
    /**
     * ONVIF layer placeholder
     * 
     * @var object
     */
    protected $onvif = '';
    /**
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Custom device templates database abstraction layer
     *
     * @var object
     */
    protected $custTemplatesDb = '';

    /**
     * Contains all custom device templates data as id=>custtpldata
     *
     * @var array
     */
    protected $allCustTemplatesData = array();


    /**
     * some predefined stuff here
     */
    const TABLE_TEMPLATES = 'custtpls';
    const URL_ME = '?module=modelcraft';
    const ROUTE_EXPLORER = 'onvifexplorer';
    const PROUTE_EXPLORE = 'explorecamera';
    const PROUTE_EXPLORE_IP = 'exploredevip';
    const PROUTE_EXPLORE_LOGIN = 'exploredevlogin';
    const PROUTE_EXPLORE_PASSWORD = 'exploredevpassword';
    const PROUTE_TPLCREATE_DEV = 'customtemplatedevicename';
    const PROUTE_TPLCREATE_PROTO = 'customtemplateproto';
    const PROUTE_TPLCREATE_MAIN = 'customtemplatemainstream';
    const PROUTE_TPLCREATE_SUB = 'customtemplatesubstream';
    const PROUTE_TPLCREATE_PORT = 'customtemplateport';
    const PROUTE_TPLCREATE_SOUND = 'customtemplatesound';
    const PROUTE_TPLCREATE_PTZ = 'customtemplateptz';
    const ROUTE_TPL_DEL = 'deletecusttpl';
    const CUSTOM_TEMPLATE_MARK = '_CUSTOM';

    public function __construct() {
        $this->initMessages();
        $this->initTemplatesDb();
        $this->loadCustomTemplates();
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
     * Initializes the Onvif object.
     *
     * This method creates a new instance of the Ponvif class and assigns it to the $onvif property.
     * The Ponvif class is responsible for handling Onvif-related operations.
     * 
     * @return void
     */
    protected function initOnvif() {
        $this->onvif = new Ponvif();
    }

    /**
     * Inits templates database abstraction layer
     *
     * @return void
     */
    protected function initTemplatesDb() {
        $this->custTemplatesDb = new NyanORM(self::TABLE_TEMPLATES);
    }

    /**
     * Loads all existing custom templates data from database
     *
     * @return void
     */
    protected function loadCustomTemplates() {
        $this->allCustTemplatesData = $this->custTemplatesDb->getAll('id');
    }


    /**
     * Polls the ONVIF capable device for information and retrieves the available sources and their stream URLs.
     *
     * @param string $ip The IP address of the camera.
     * @param string $login The login username for accessing the camera.
     * @param string $password The login password for accessing the camera.
     * 
     * @return array
     */
    public function pollDevice($ip, $login, $password) {
        $result = array();
        if (zb_PingICMP($ip)) {
            $this->initOnvif();
            $this->onvif->setIPAddress($ip);
            $this->onvif->setUsername($login);
            $this->onvif->setPassword($password);
            try {
                $this->onvif->initialize();
                $sources = $this->onvif->getSources();
                if (!empty($sources)) {
                    $result['sources'] = $sources[0];
                    foreach ($sources[0] as $io => $each) {
                        if (isset($each['profiletoken'])) {
                            $streamUri = $this->onvif->media_GetStreamUri($each['profiletoken']);
                            $result['sources'][$io]['url'] = $streamUri;
                            if (!empty($streamUri)) {
                                $parsedUrl = parse_url($streamUri);
                                if (!empty($parsedUrl)) {
                                    $result['sources'][$io]['urldata'] = $parsedUrl;
                                    if (!isset($result['sources'][$io]['urldata']['query'])) {
                                        $result['sources'][$io]['urldata']['query'] = '';
                                    }
                                }
                            }
                        } else {
                            unset($result['sources'][$io]);
                        }
                    }
                }
            } catch (Exception $e) {
                //nothing here right now
            }
        }
        return ($result);
    }
    /**
     * Renders the ONVIF device explore form.
     *
     * This method generates the HTML code for an explore form, which includes input fields for IP, login, password, and a submit button.
     *
     * @return string 
     */
    public function renderExploreForm() {
        $result = '';
        $inputs = wf_HiddenInput(self::PROUTE_EXPLORE, 'true');
        $inputs .= wf_TextInput(self::PROUTE_EXPLORE_IP, __('IP'), ubRouting::post(self::PROUTE_EXPLORE_IP), false, 16, 'ip') . ' ';
        $inputs .= wf_TextInput(self::PROUTE_EXPLORE_LOGIN, __('Login'), ubRouting::post(self::PROUTE_EXPLORE_LOGIN), false, 12, '') . ' ';
        $inputs .= wf_PasswordInput(self::PROUTE_EXPLORE_PASSWORD, __('Password'), ubRouting::post(self::PROUTE_EXPLORE_PASSWORD), false, 12, true) . ' ';
        $inputs .= wf_Submit(__('Explore'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    public function renderTemplateCreationForm($deviceData) {
        $result = '';
        $availableSources = array();
        $port = 554;
        $proto = 'rtsp';
        $sound = 0;
        $ptz = 0;
        if (!empty($deviceData)) {
            if (isset($deviceData['sources'])) {
                foreach ($deviceData['sources'] as $io => $each) {
                    if (isset($each['profilename']) and isset($each['urldata'])) {
                        $profileLabel = $each['profilename'];
                        $shortUrl = $each['urldata']['path'];
                        if (!empty($each['urldata']['query'])) {
                            $shortUrl .= '?' . $each['urldata']['query'];
                        }
                        $port = $each['urldata']['port'];
                        $proto = $each['urldata']['scheme'];
                        $availableSources[$shortUrl] = $profileLabel . ' (' . $each['width'] . 'x' . $each['height'] . ')';
                    }
                }
                //some form here
                $inputs = wf_TextInput(self::PROUTE_TPLCREATE_DEV, __('Template name'), '', true, 16, '');
                $inputs .= wf_Selector(self::PROUTE_TPLCREATE_MAIN, $availableSources, __('Mainstream'), '', true);
                $inputs .= wf_Selector(self::PROUTE_TPLCREATE_SUB, $availableSources, __('Substream'), '', true);
                $inputs .= wf_CheckInput(self::PROUTE_TPLCREATE_SOUND, __('Sound'), true, false);
                $inputs .= wf_CheckInput(self::PROUTE_TPLCREATE_PTZ, __('PTZ'), true, false);
                $inputs .= wf_HiddenInput(self::PROUTE_TPLCREATE_PORT, $port);
                $inputs .= wf_HiddenInput(self::PROUTE_TPLCREATE_PROTO, $proto);
                $inputs .= wf_Submit(__('Create'));

                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('Device media sources is empty'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Device polling data is empty'), 'error');
        }
        return ($result);
    }

    /**
     * Renders polled device data with few controls
     *
     * @param array $deviceData
     * 
     * @return string
     */
    public function renderPollingResults($deviceData) {
        $result = '';
        if (!empty($deviceData)) {
            if (isset($deviceData['sources'])) {
                $cells = wf_TableCell(__('Profile name'));
                $cells .= wf_TableCell(__('Token'));
                $cells .= wf_TableCell(__('Encoding'));
                $cells .= wf_TableCell(__('Resolution'));
                $cells .= wf_TableCell(__('MP'));
                $cells .= wf_TableCell(__('FPS'));
                $cells .= wf_TableCell(__('Bitrate'));
                $cells .= wf_TableCell(__('Protocol'));
                $cells .= wf_TableCell(__('Port'));
                $cells .= wf_TableCell(__('URL'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($deviceData['sources'] as $io => $each) {
                    $cells = wf_TableCell($each['profilename']);
                    $cells .= wf_TableCell($each['profiletoken']);
                    $cells .= wf_TableCell($each['encoding']);
                    $pix = $each['width'] * $each['height'];
                    $mpix = round($pix / 1000000, 1);
                    $cells .= wf_TableCell($each['width'] . 'x' . $each['height'], '', '', 'sorttable_customkey="' . $pix . '"');
                    $cells .= wf_TableCell($mpix);
                    $cells .= wf_TableCell($each['fps']);
                    $cells .= wf_TableCell($each['bitrate'] . ' ' . __('Kbit/s'));
                    $cells .= wf_TableCell($each['urldata']['scheme']);
                    $cells .= wf_TableCell($each['urldata']['port']);
                    $shortUrl = $each['urldata']['path'];
                    if (!empty($each['urldata']['query'])) {
                        $shortUrl .= '?' . $each['urldata']['query'];
                    }
                    $cells .= wf_TableCell($shortUrl);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
                $result .= wf_delimiter();
                $rawData = wf_tag('pre') . print_r($deviceData, true) . wf_tag('pre', true);
                $result .= wf_modal(wf_img('skins/brain.png') . ' ' . __('Device inside'), __('Device inside'), $rawData, 'ubButton', '900', '600');
                $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create device model template'), __('Create device model template'), $this->renderTemplateCreationForm($deviceData), 'ubButton');
            } else {
                $result .= $this->messages->getStyledMessage(__('Device media sources is empty'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Device polling data is empty'), 'error');
        }
        return ($result);
    }
    /**
     * Creates a custom device template database record with the given parameters.
     *
     * @param string $name The name of the custom template.
     * @param string $proto The protocol of the custom template.
     * @param string $main The main parameter of the custom template.
     * @param string $sub The sub parameter of the custom template.
     * @param int $rtspport The RTSP port of the custom template.
     * @param int $sound The sound parameter of the custom template. Default is 0.
     * @param int $ptz The PTZ parameter of the custom template. Default is 0.
     * 
     * @return void
     */
    public function createCustomTemplate($name, $proto, $main, $sub, $rtspport, $sound = 0, $ptz = 0) {
        $nameF = ubRouting::filters($name, 'mres');
        $protoF = ubRouting::filters($proto, 'mres');
        $mainF = ubRouting::filters($main, 'mres');
        $subF = ubRouting::filters($sub, 'mres');
        $rtspport = ubRouting::filters($rtspport, 'int');
        $sound = ($sound) ? 1 : 0;
        $ptz = ($ptz) ? 1 : 0;

        $this->custTemplatesDb->data('name', $nameF);
        $this->custTemplatesDb->data('proto', $protoF);
        $this->custTemplatesDb->data('main', $mainF);
        $this->custTemplatesDb->data('sub', $subF);
        $this->custTemplatesDb->data('rtspport', $rtspport);
        $this->custTemplatesDb->data('sound', $sound);
        $this->custTemplatesDb->data('ptz', $ptz);
        $this->custTemplatesDb->create();
        $newId = $this->custTemplatesDb->getLastId();
        log_register('MODELCRAFT CREATE [' . $newId . '] NAME `' . $name . '`');
        //reload data from database
        $this->loadCustomTemplates();
        //generate new template
        $this->generateCustomTemplateFile($newId);
    }

    /**
     * Renders the module controls
     *
     * @return string
     */
    public function renderControls() {
        $result = '';
        if (!ubRouting::checkGet(self::ROUTE_EXPLORER)) {
            $result .= wf_BackLink(Models::URL_ME);
            $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_EXPLORER . '=true', web_icon_search() . ' ' . __('ONVIF device explorer'), false, 'ubButton');
        } else {
            $result .= wf_BackLink(self::URL_ME);
        }

        return ($result);
    }

    /**
     * Deletes a custom template by its ID.
     *
     * @param int $templateId The ID of the template to be deleted.
     * 
     * @return void|string
     */
    public function deleteCustomTemplate($templateId) {
        $result = '';
        $templateId = ubRouting::filters($templateId, 'int');
        if (isset($this->allCustTemplatesData[$templateId])) {
            $templateName = $templateId . self::CUSTOM_TEMPLATE_MARK;
            $templateLabel = $this->allCustTemplatesData[$templateId]['name'];
            $models = new Models();
            if (!$models->isTemplateProtected($templateName)) {
                $this->custTemplatesDb->where('id', '=', $templateId);
                $this->custTemplatesDb->delete();
                $this->destroyCustomTemplateFile($templateName);
                log_register('MODELCRAFT DELETE [' . $templateId . '] NAME `' . $templateLabel . '`');
            } else {
                $result .= __('Something went wrong') . ': ' . __('Template now is used');
            }
        } else {
            $result .= __('Something went wrong') . ': ' . __('Template') . ' [' . $templateId . '] ' . __('not exists');
        }
        return ($result);
    }

    /**
     * Renders custom device templates list
     *
     * @return void
     */
    public function renderCustomTemplatesList() {
        $result = '';
        if (!empty($this->allCustTemplatesData)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allCustTemplatesData as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['name']);
                $delUrl = self::URL_ME . '&' . self::ROUTE_TPL_DEL . '=' . $each['id'];
                $actLinks = wf_JSAlert($delUrl, web_delete_icon(), $this->messages->getDeleteAlert());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
            //sync templates on render
            $this->syncCustomTemplates();
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Syncs the custom templates on FS with existing in database
     *
     * @return void
     */
    public function syncCustomTemplates() {
        $customTemplates = array();
        $customTemplatesRaw = rcms_scandir(Models::CUSTOM_TEMPLATES_PATH);
        if (!empty($customTemplatesRaw)) {
            foreach ($customTemplatesRaw as $io => $eachTemplate) {
                $templateData = rcms_parse_ini_file(Models::CUSTOM_TEMPLATES_PATH . $eachTemplate);
                if (is_array($templateData)) {
                    $customTemplates[$eachTemplate] = $templateData;
                }
            }
        }

        //may be some templates is missing?
        if (!empty($this->allCustTemplatesData)) {
            foreach ($this->allCustTemplatesData as $io => $each) {
                $templateName = $each['id'] . self::CUSTOM_TEMPLATE_MARK;
                //sync is required
                if (!isset($customTemplates[$templateName])) {
                    $this->generateCustomTemplateFile($each['id']);
                    log_register('MODELCRAFT SYNC [' . $each['id'] . '] TEMPLATE `' . $templateName . '`');
                }
            }
        }
    }

    /**
     * Deletes a custom template from filesystem
     *
     * @param string $templateName The name of the template file to be deleted.
     * @return void
     */
    protected function destroyCustomTemplateFile($templateName) {
        if (!empty($templateName)) {
            $fsPath = Models::CUSTOM_TEMPLATES_PATH . $templateName;
            if (file_exists($fsPath)) {
                unlink($fsPath);
                log_register('MODELCRAFT DESTROY TEMPLATE `' . $templateName . '`');
            }
        }
    }

    /**
     * Generates a custom device template based on the provided template ID.
     *
     * @param int $templateId The ID of the template.
     * 
     * @return void
     */
    protected function generateCustomTemplateFile($templateId) {
        $templateId = ubRouting::filters($templateId, 'int');
        if (isset($this->allCustTemplatesData[$templateId])) {
            $templateName = $templateId . self::CUSTOM_TEMPLATE_MARK;
            $fsPath = Models::CUSTOM_TEMPLATES_PATH . $templateName;
            $devData = $this->allCustTemplatesData[$templateId];

            $templateBody = 'DEVICE="' . $devData['name'] . '"' . PHP_EOL;
            $templateBody .= 'PROTO="' . $devData['proto'] . '"' . PHP_EOL;
            $templateBody .= 'MAIN_STREAM="' . $devData['main'] . '"' . PHP_EOL;
            $templateBody .= 'SUB_STREAM="' . $devData['sub'] . '"' . PHP_EOL;
            $templateBody .= 'RTSP_PORT=' . $devData['rtspport'] . '' . PHP_EOL;
            $templateBody .= 'HTTP_PORT=80' . PHP_EOL;
            $templateBody .= 'SOUND=' . $devData['sound'] . '' . PHP_EOL;
            $templateBody .= 'PTZ=' . $devData['ptz'] . '' . PHP_EOL;
            file_put_contents($fsPath, $templateBody);
            chmod($fsPath, 0777);
            log_register('MODELCRAFT GENERATE [' . $templateId . '] TEMPLATE `' . $templateName . '`');
        }
    }
}
