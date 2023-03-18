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

    public function __construct() {
        $this->initStoragesDb();
        $this->loadStorages();
    }

    /**
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initStoragesDb() {
        $this->storagesDb = new NyanORM('storages');
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
            $result = __('Storage path or name is empty');
        }
        return($result);
    }

}
