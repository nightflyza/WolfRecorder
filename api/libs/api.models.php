<?php

class Models {

    /**
     * Models database abstraction layer placeholder
     *
     * @var object
     */
    protected $modelsDb = '';

    /**
     * Contains all available models as id=>modelData
     *
     * @var array
     */
    protected $allModels = array();

    /**
     * Contains all available models config templates as name=>data
     *
     * @var array
     */
    protected $allTemplatesData = array();

    /**
     * Contains all device template names as name=>deviceName
     *
     * @var array
     */
    protected $allTemplateNames = array();

    /**
     * Contains system messages helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * some predefined stuff here
     */
    const TEMPLATES_PATH = 'config/modeltemplates/';
    const CUSTOM_TEMPLATES_PATH = 'config/mymodeltemplates/';
    const URL_ME = '?module=models';
    const PROUTE_NEWMODELNAME = 'newmodelname';
    const PROUTE_NEWMODELTPL = 'newmodeltemplate';
    const ROUTE_DELMODEL = 'deletemodelid';
    const DATA_TABLE = 'models';

    public function __construct() {
        $this->initMessages();
        $this->initModelsDb();
        $this->loadAllModels();
        $this->loadAllTemplates();
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
     * Inits models database abstraction layer
     * 
     * @return void
     */
    protected function initModelsDb() {
        $this->modelsDb = new NyanORM(self::DATA_TABLE);
    }

    /**
     * loads all existing models from database into protected property
     * 
     * @return void
     */
    protected function loadAllModels() {
        $this->allModels = $this->modelsDb->getAll('id');
    }

    /**
     * Preloads all available model templates from FS
     * 
     * @return void
     */
    protected function loadAllTemplates() {
        //basic templates loading
        $templatesRaw = rcms_scandir(self::TEMPLATES_PATH);
        if (!empty($templatesRaw)) {
            foreach ($templatesRaw as $io => $eachTemplate) {
                $templateData = rcms_parse_ini_file(self::TEMPLATES_PATH . $eachTemplate);
                if (is_array($templateData)) {
                    $this->allTemplatesData[$eachTemplate] = $templateData;
                    $this->allTemplateNames[$eachTemplate] = $templateData['DEVICE'];
                }
            }
        }
        //custom user templates after-loading as overrides
        $customTemplatesRaw = rcms_scandir(self::CUSTOM_TEMPLATES_PATH);
        if (!empty($customTemplatesRaw)) {
            foreach ($customTemplatesRaw as $io => $eachTemplate) {
                $templateData = rcms_parse_ini_file(self::CUSTOM_TEMPLATES_PATH . $eachTemplate);
                if (is_array($templateData)) {
                    $this->allTemplatesData[$eachTemplate] = $templateData;
                    $this->allTemplateNames[$eachTemplate] = $templateData['DEVICE'];
                }
            }
        }
    }

    /**
     * Creates new model in database
     * 
     * @param string $modelName
     * @param string $modelTemplate
     * 
     * @return void/string
     */
    public function create($modelName, $modelTemplate) {
        $result = '';
        $modelNameF = ubRouting::filters($modelName, 'mres');
        $templateF = ubRouting::filters($modelTemplate, 'mres');
        if (!empty($modelNameF)) {
            if (isset($this->allTemplatesData[$templateF])) {
                $this->modelsDb->data('modelname', $modelNameF);
                $this->modelsDb->data('template', $templateF);
                $this->modelsDb->create();
                $newId = $this->modelsDb->getLastId();
                log_register('MODEL CREATE [' . $newId . '] NAME `' . $modelName . '` TEMPLATE `' . $modelTemplate . '`');
            } else {
                $result .= __('Template not exists');
            }
        } else {
            $result .= __('Model name is empty');
        }
        return($result);
    }

    /**
     * Renders model creation form
     * 
     * @return string
     */
    public function renderCreationForm() {
        $result = '';
        $inputs = wf_TextInput(self::PROUTE_NEWMODELNAME, __('Name'), '', false, 20) . ' ';
        $inputs .= wf_SelectorSearchable(self::PROUTE_NEWMODELTPL, $this->allTemplateNames, __('Template'), '', false) . ' ';
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Checks is some model used by some cameras or not?
     * 
     * @param int $modelId
     * 
     * @return bool
     */
    protected function isProtected($modelId) {
        $result = false;
        $modelId = ubRouting::filters($modelId, 'int');
        //TODO: some checks here to check is this model used for some cameras or not?
        return($result);
    }

    /**
     * Deletes some model from database
     * 
     * @param int $modelId
     * 
     * @return void/string
     */
    public function delete($modelId) {
        $result = '';
        $modelId = ubRouting::filters($modelId, 'int');
        if (isset($this->allModels[$modelId])) {
            if (!$this->isProtected($modelId)) {
                $this->modelsDb->where('id', '=', $modelId);
                $this->modelsDb->delete();
                log_register('MODEL DELETE [' . $modelId . ']');
            } else {
                $result .= __('Model now is used');
            }
        } else {
            $result .= __('Model not exists') . ' [' . $modelId . ']';
        }
        return($result);
    }

    /**
     * Renders available models list
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        if (!empty($this->allModels)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Template'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allModels as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['modelname']);
                $cells .= wf_TableCell($each['template']);
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DELMODEL . '=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Returns all available model names as id=>modelName
     * 
     * @return array
     */
    public function getAllModelNames() {
        $result = array();
        if (!empty($this->allModels)) {
            foreach ($this->allModels as $io => $each) {
                $result[$each['id']] = $each['modelname'];
            }
        }
        return($result);
    }

}
