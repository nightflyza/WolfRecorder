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
    const PATH_HOWL = 'howl/';
    const PROUTE_PATH = 'newstoragepath';
    const PROUTE_NAME = 'newstoragename';
    const ROUTE_DEL = 'deletestorageid';
    const PROUTE_ED_STORAGE = 'editstorageid';
    const PROUTE_ED_NAME = 'editstoragename';
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
        $this->storagesDb->orderBy('id', 'DESC');
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
        return ($result);
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
        return ($result);
    }

    /**
     * Returns all existing storages names as id=>name
     * 
     * @return array
     */
    public function getAllStorageNamesLocalized() {
        $result = array();
        if (!empty($this->allStorages)) {
            foreach ($this->allStorages as $io => $each) {
                $result[$each['id']] = __($each['name']);
            }
        }
        return ($result);
    }

    /**
     * Returns all storages data as id=>storageData
     * 
     * @return array
     */
    public function getAllStoragesData() {
        return ($this->allStorages);
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
        return ($result);
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
        if (!empty($pathF) and ! empty($nameF)) {
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
        return ($result);
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

        return ($result);
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
        return ($result);
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
        return ($result);
    }

    /**
     * Renders storage editing form
     * 
     * @param int $storageId
     * 
     * @return string
     */
    protected function renderEditForm($storageId) {
        $result = '';
        $storageId = ubRouting::filters($storageId, 'int');
        if (isset($this->allStorages[$storageId])) {
            $inputs = wf_HiddenInput(self::PROUTE_ED_STORAGE, $storageId);
            $inputs .= wf_TextInput(self::PROUTE_ED_NAME, __('Name'), $this->allStorages[$storageId]['name'], false, 20) . ' ';
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Changes storage name in database
     * 
     * @param int $storageId
     * @param string $storageName
     * 
     * @return void
     */
    public function saveName($storageId, $storageName) {
        $storageId = ubRouting::filters($storageId, 'int');
        $storageNameF = ubRouting::filters($storageName, 'mres');
        if ($storageId and $storageNameF) {
            if (isset($this->allStorages[$storageId])) {
                $storagePath = $this->allStorages[$storageId]['path'];
                $this->storagesDb->where('id', '=', $storageId);
                $this->storagesDb->data('name', $storageNameF);
                $this->storagesDb->save();
                log_register('STORAGE EDIT [' . $storageId . '] PATH `' . $storagePath . '` NAME `' . $storageName . '`');
            }
        }
    }

    /**
     * Renders available storages list
     * 
     * @return string
     */
    public function renderList() {
        $hwInfo = new SystemHwInfo();

        $result = '';
        if (!empty($this->allStorages)) {
            $allStoragesCams = $this->getAllStoragesCamerasCount();
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Path'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('State'));
            $cells .= wf_TableCell(__('Cameras'));
            $cells .= wf_TableCell(__('Capacity'));
            $cells .= wf_TableCell(__('Free'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allStorages as $io => $each) {
                $storageSizeLabel =  '-';
                $storageFreeLabel = '-';
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['path']);
                $cells .= wf_TableCell(__($each['name']));
                $storageState = ($this->checkPath($each['path'])) ? true : false;
                $stateIcon = web_bool_led($storageState);
                if ($storageState) {
                    $diskStats = $hwInfo->getDiskStat($each['path']);
                    $storageSizeLabel =  wr_convertSize( $diskStats['total']);
                    $storageFreeLabel =  wr_convertSize($diskStats['free']);
                }
                $cells .= wf_TableCell($stateIcon);
                $cells .= wf_TableCell($allStoragesCams[$each['id']]);
                $cells .= wf_TableCell($storageSizeLabel);
                $cells .= wf_TableCell($storageFreeLabel);
                $actControls = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DEL . '=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $actControls .= wf_modalAuto(web_edit_icon(), __('Edit') . ' `' . __($each['name']) . '`', $this->renderEditForm($each['id']));
                $cells .= wf_TableCell($actControls);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable resp-table');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Returns all storages cameras counters as storeageId=>camerasCount
     * 
     * @return array
     */
    protected function getAllStoragesCamerasCount() {
        $result = array();
        if (!empty($this->allStorages)) {
            foreach ($this->allStorages as $io => $each) {
                $result[$each['id']] = 0;
            }
            $camerasDb = new NyanORM(Cameras::DATA_TABLE);
            $camerasDb->selectable('id,storageid');
            $allCamerasStorages = $camerasDb->getAll();
            if (!empty($allCamerasStorages)) {
                foreach ($allCamerasStorages as $io => $eachCam) {
                    $result[$eachCam['storageid']]++;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns Id of least used storage
     * 
     * @return int
     */
    public function getLeastUsedStorage() {
        $result = 0;
        $allStoragesCamerasCount = $this->getAllStoragesCamerasCount();
        if (!empty($allStoragesCamerasCount)) {
            $result = array_search(min($allStoragesCamerasCount), $allStoragesCamerasCount);
        }
        return ($result);
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
        return ($result);
    }

    /**
     * Returns all chunks stored in some channel as timestamp=>fullPath
     * 
     * @param int $storageId
     * @param string $channel
     * 
     * @return array
     */
    public function getChannelChunks($storageId, $channel) {
        $result = array();
        $storageId = ubRouting::filters($storageId, 'int');
        $channel = ubRouting::filters($channel, 'mres');
        if ($storageId and $channel) {
            if (isset($this->allStorages[$storageId])) {
                $storagePath = $this->allStorages[$storageId]['path'];
                $storageLastChar = substr($storagePath, -1);
                if ($storageLastChar != '/') {
                    $storagePath .= '/';
                }
                if (file_exists($storagePath)) {
                    if (file_exists($storagePath . $channel)) {
                        $chunksExt = Recorder::CHUNKS_EXT;
                        $allChunksNames = scandir($storagePath . $channel);
                        if (!empty($allChunksNames)) {
                            foreach ($allChunksNames as $io => $eachFileName) {
                                if ($eachFileName != '.' and $eachFileName != '..') {
                                    $cleanChunkTimeStamp = str_replace($chunksExt, '', $eachFileName);
                                    $result[$cleanChunkTimeStamp] = $storagePath . $channel . '/' . $eachFileName;
                                }
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns all chunks stored in some channel with their allocated space as timestamp=>path/size
     * 
     * @param int $storageId
     * @param string $channel
     * 
     * @return array
     */
    public function getChunksAllocSpaces($storageId, $channel) {
        $result = array();
        $storageId = ubRouting::filters($storageId, 'int');
        $channel = ubRouting::filters($channel, 'mres');
        if ($storageId and $channel) {
            $channelChunks = $this->getChannelChunks($storageId, $channel);
            if (!empty($channelChunks)) {
                foreach ($channelChunks as $chunkTimeStamp => $eachChunkPath) {
                    $result[$chunkTimeStamp]['path'] = $eachChunkPath;
                    $result[$chunkTimeStamp]['size'] = filesize($eachChunkPath);
                }
            }
        }
        return ($result);
    }

    /**
     * Returns total size of all chunks in getChunksAllocSpaces data
     * 
     * @param array $chunksListAlloc
     * 
     * @return int 
     */
    public function calcChunksListSize($chunksListAlloc) {
        $result = 0;
        if (!empty($chunksListAlloc)) {
            foreach ($chunksListAlloc as $timeStamp => $chunksData) {
                $result += $chunksData['size'];
            }
        }
        return ($result);
    }

    /**
     * Returns bytes count that some channel currently stores
     * 
     * @param int $storageId
     * @param string $channel
     * 
     * @return string
     */
    public function getChannelSize($storageId, $channel) {
        $result = 0;
        $storageId = ubRouting::filters($storageId, 'int');
        $channel = ubRouting::filters($channel, 'mres');
        if ($storageId and $channel) {
            $channelChunks = $this->getChannelChunks($storageId, $channel);
            if (!empty($channelChunks)) {
                foreach ($channelChunks as $io => $eachChunk) {
                    $result += filesize($eachChunk);
                }
            }
        }
        return ($result);
    }

    /**
     * Returns bytes count that some channel chunks list contains
     * 
     * @param array $chunksList
     * 
     * @return string
     */
    public function getChannelChunksSize($chunksList) {
        $result = 0;
        if (!empty($chunksList)) {
            foreach ($chunksList as $io => $eachChunk) {
                $result += filesize($eachChunk);
            }
        }
        return ($result);
    }

    /**
     * Filters some chunks array and lefts only chunks between some timestamps in range
     * 
     * @param array $chunksList
     * @param int $timeFrom
     * @param int $timeTo
     * 
     * @return array
     */
    public function filterChunksTimeRange($chunksList, $timeFrom, $timeTo) {
        $result = array();
        if (!empty($chunksList)) {
            foreach ($chunksList as $eachTimestamp => $eachChunkPath) {
                if (($eachTimestamp > $timeFrom) and ($eachTimestamp < $timeTo)) {
                    $result[$eachTimestamp] = $eachChunkPath;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns size in bytes of all chunks in list
     * 
     * @param array $chunksList
     * 
     * @return int
     */
    public function getChunksSize($chunksList) {
        $result = 0;
        if (!empty($chunksList)) {
            foreach ($chunksList as $timeStamp => $eachChunk) {
                if (file_exists($eachChunk)) {
                    $result += filesize($eachChunk);
                }
            }
        }
        return ($result);
    }

    /**
     * Allocates path in storage for channel recording if required
     * 
     * @param string $storagePath
     * @param string $channel
     * 
     * @return string/bool
     */
    protected function allocateChannel($storagePath, $channel) {
        $result = false;
        $delimiter = '';
        if (!empty($storagePath) and ! empty($channel)) {
            if (file_exists($storagePath)) {
                if (is_dir($storagePath)) {
                    if (is_writable($storagePath)) {
                        $pathLastChar = substr($storagePath, -1);
                        if ($pathLastChar != '/') {
                            $delimiter = '/';
                        }
                        $chanDirName = $storagePath . $delimiter . $channel;
                        $fullPath = $chanDirName . '/';
                        $howlLink = self::PATH_HOWL . $channel;
                        //allocate channel dir
                        if (!file_exists($fullPath)) {
                            //creating new directory
                            mkdir($fullPath, 0777);
                            chmod($fullPath, 0777);
                            log_register('STORAGE ALLOCATED `' . $storagePath . '` CHANNEL `' . $channel . '`');
                        }

                        //linking to howl?
                        if (!file_exists($howlLink)) {
                            symlink($chanDirName, $howlLink);
                            chmod($howlLink, 0777);
                            log_register('STORAGE LINKED `' . $storagePath . '` HOWL `' . $channel . '`');
                        }

                        //seems ok?
                        if (is_writable($fullPath)) {
                            $result = $fullPath;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Migrates howl symlink to new storage
     * 
     * @param int $newStorageId
     * @param string $channel
     * 
     * @return void
     */
    public function migrateChannel($newStorageId, $channel) {
        $newStorageId = ubRouting::filters($newStorageId, 'int');
        $channel = ubRouting::filters($channel, 'mres');
        if (isset($this->allStorages[$newStorageId])) {
            $storageData = $this->getStorageData($newStorageId);
            $storagePath = $storageData['path'];
            $howlLink = self::PATH_HOWL . $channel;
            if (file_exists($howlLink)) {
                //cleanin old howl link
                unlink($howlLink);
                //new storage channel alocation
                $this->allocateChannel($storagePath, $channel);
                log_register('STORAGE MIGRATED `' . $storagePath . '` HOWL `' . $channel . '`');
            }
        }
    }

    /**
     * Allocates path in storage for channel recording if required
     * 
     * @param int $storageId
     * @param string $channel
     * 
     * @return string/bool
     */
    public function initChannel($storageId, $channel) {
        $result = false;
        $storageId = ubRouting::filters($storageId, 'int');
        $channel = ubRouting::filters($channel, 'mres');

        if (isset($this->allStorages[$storageId])) {
            $storagePath = $this->allStorages[$storageId]['path'];
            $result = $this->allocateChannel($storagePath, $channel);
        }
        return ($result);
    }

    /**
     * Deletes allocated channel with all data inside
     * 
     * @param int $storageId
     * @param string $channel
     * 
     * @return void
     */
    public function flushChannel($storageId, $channel) {
        $storageId = ubRouting::filters($storageId, 'int');
        $channel = ubRouting::filters($channel, 'mres');
        if (isset($this->allStorages[$storageId])) {
            $storagePath = $this->allStorages[$storageId]['path'];
            $delimiter = '';
            if (!empty($storagePath) and ! empty($channel)) {
                if (file_exists($storagePath)) {
                    if (is_dir($storagePath)) {
                        if (is_writable($storagePath)) {
                            $pathLastChar = substr($storagePath, -1);
                            if ($pathLastChar != '/') {
                                $delimiter = '/';
                            }
                            $fullPath = $storagePath . $delimiter . $channel;
                            //seems ok?
                            if (is_writable($fullPath)) {
                                //unlink howl
                                unlink(self::PATH_HOWL . $channel);
                                //destroy channel dir
                                rcms_delete_files($fullPath, true);
                                //archive playlist cleanup
                                $archPlaylistName = self::PATH_HOWL . $channel . Archive::PLAYLIST_MASK;
                                if (file_exists($archPlaylistName)) {
                                    rcms_delete_files($archPlaylistName);
                                }
                                log_register('STORAGE FLUSH [' . $storageId . '] PATH `' . $storagePath . '` CHANNEL `' . $channel . '`');
                            }
                        }
                    }
                }
            }
        }
    }
}
