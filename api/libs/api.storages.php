<?php

/**
 * Storages management class implementation
 */
class Storages {

    /**
     * Storages database abstraction layer placeholder
     *
     * @var object
     */
    protected $storagesDb = '';

    /**
     * Contains all available storages as id=>storageData
     *
     * @var array
     */
    protected $allStorages = array();

    /**
     * Contains system messages helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some predefined stuff here
     */
    const PROUTE_PATH = 'newstoragepath';
    const PROUTE_NAME = 'newstoragename';
    const ROUTE_DEL = 'deletestorageid';
    const URL_ME = '?module=storages';
    const DATA_TABLE = 'storages';

    public function __construct() {
        $this->initMessages();
        $this->initStoragesDb();
        $this->loadStorages();
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
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initStoragesDb() {
        $this->storagesDb = new NyanORM(self::DATA_TABLE);
    }

    /**
     * Loads all available storages from database
     * 
     * @return void
     */
    protected function loadStorages() {
        $this->allStorages = $this->storagesDb->getAll('id');
    }

    /**
     * Returns storage data by its ID
     * 
     * @param int $storageId
     * 
     * @return array
     */
    public function getStorageData($storageId) {
        $result = array();
        if (isset($this->allStorages[$storageId])) {
            $result = $this->allStorages[$storageId];
        }
        return($result);
    }

    /**
     * Returns all existing storages names as id=>name
     * 
     * @return array
     */
    public function getAllStorageNames() {
        $result = array();
        if (!empty($this->allStorages)) {
            foreach ($this->allStorages as $io => $each) {
                $result[$each['id']] = $each['name'];
            }
        }
        return($result);
    }

    /**
     * Returns all storages data as id=>storageData
     * 
     * @return array
     */
    public function getAllStoragesData() {
        return($this->allStorages);
    }

    /**
     * Checks is some path not used by another storage?
     * 
     * @param string $path
     * 
     * @return bool
     */
    protected function isPathUnique($path) {
        $result = true;
        if (!empty($this->allStorages)) {
            foreach ($this->allStorages as $io => $each) {
                if ($each['path'] == $path) {
                    $result = false;
                }
            }
        }
        return($result);
    }

    /**
     * Creates new storage in database
     * 
     * @param string $path
     * @param string $name
     * 
     * @return void/string on error
     */
    public function create($path, $name) {
        $result = '';
        $pathF = ubRouting::filters($path, 'mres');
        $nameF = ubRouting::filters($name, 'mres');
        if (!empty($pathF) AND ! empty($nameF)) {
            if ($this->isPathUnique($pathF)) {
                if (file_exists($pathF)) {
                    if (is_dir($pathF)) {
                        if (is_writable($pathF)) {
                            $this->storagesDb->data('path', $pathF);
                            $this->storagesDb->data('name', $nameF);
                            $this->storagesDb->create();
                            $storageId = $this->storagesDb->getLastId();
                            log_register('STORAGE CREATE [' . $storageId . '] PATH `' . $path . '` NAME `' . $name . '`');
                        } else {
                            $result = __('Storage path is not writable');
                        }
                    } else {
                        $result = __('Storage path is not directory');
                    }
                } else {
                    $result = __('Storage path not exists');
                }
            } else {
                $result = __('Another storage with such path is already exists');
            }
        } else {
            $result = __('Storage path or name is empty');
        }
        return($result);
    }

    /**
     * Deletes some storage from database
     * 
     * @param int $storageId
     * 
     * @return void/string on error
     */
    public function delete($storageId) {
        $result = '';
        $storageId = ubRouting::filters($storageId, 'int');
        if (isset($this->allStorages[$storageId])) {
            if (!$this->isProtected($storageId)) {
                $this->storagesDb->where('id', '=', $storageId);
                $this->storagesDb->delete();
                log_register('STORAGE DELETE [' . $storageId . ']');
            } else {
                $result = __('You can not delete storage which is in usage');
            }
        } else {
            $result = __('No such storage') . ' [' . $storageId . ']';
        }

        return($result);
    }

    /**
     * Renders storage creation form
     * 
     * @return string
     */
    public function renderCreationForm() {
        $result = '';
        $inputs = wf_TextInput(self::PROUTE_PATH, __('Path'), '', false, 20);
        $inputs .= wf_TextInput(self::PROUTE_NAME, __('Name'), '', false, 20);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Checks is storage path exists, valid and writtable?
     * 
     * @param string $path
     * 
     * @return bool
     */
    public function checkPath($path) {
        $result = false;
        if (file_exists($path)) {
            if (is_dir($path)) {
                if (is_writable($path)) {
                    $result = true;
                }
            }
        }
        return($result);
    }

    /**
     * Renders available storages list
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        if (!empty($this->allStorages)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Path'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('State'));
            $cells .= wf_TableCell(__('Capacity'));
            $cells .= wf_TableCell(__('Free'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allStorages as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['path']);
                $cells .= wf_TableCell(__($each['name']));
                $storageState = ($this->checkPath($each['path'])) ? true : false;
                $stateIcon = web_bool_led($storageState);
                $cells .= wf_TableCell($stateIcon);
                $storageSize = @disk_total_space($each['path']);
                $storageFree = @disk_free_space($each['path']);
                $storageSizeLabel = ($storageState) ? stg_convert_size($storageSize) : '-';
                $storageFreeLabel = ($storageState) ? stg_convert_size($storageFree) : '-';
                $cells .= wf_TableCell($storageSizeLabel);
                $cells .= wf_TableCell($storageFreeLabel);
                $actControls = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DEL . '=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells .= wf_TableCell($actControls);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Checks is some storage used by some cameras?
     * 
     * @param int $storageId
     * 
     * @return bool
     */
    protected function isProtected($storageId) {
        $result = true;
        $storageId = ubRouting::filters($storageId, 'int');
        $camerasDb = new NyanORM(Cameras::DATA_TABLE);
        $camerasDb->where('storageid', '=', $storageId);
        $camerasDb->selectable('id');
        $usedByCameras = $camerasDb->getAll();
        if (!$usedByCameras) {
            $result = false;
        }
        return($result);
    }

}
