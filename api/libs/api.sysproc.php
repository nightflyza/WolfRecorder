<?php

/**
 * System background process lookup 
 */
class SysProc {

    /**
     * Contains binpaths.ini config as key=>value
     *
     * @var array
     */
    protected $binPaths = array();

    /**
     * Contains processes list loaded at instance creation as pid=>processString
     *
     * @var array
     */
    protected $runningProcessList = array();


    public function __construct($loadProcList = true) {
        $this->loadConfigs();
        if ($loadProcList) {
            $this->setProcessList();
        }
    }

    /**
     * Loads some required configs
     * 
     * @global $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->binPaths = $ubillingConfig->getBinpaths();
    }

    /**
     * Sets current instance process list
     *
     * @return void
     */
    public function setProcessList() {
        $this->runningProcessList = $this->getAllProcessPids();
    }

    /**
     * Returns all running process PID-s array as pid=>processString
     * 
     * @return array
     */
    public function getAllProcessPids() {
        $result = array();
        $command = $this->binPaths['PS'] . ' ax';
        $rawResult = shell_exec($command);
        if (!empty($rawResult)) {
            $rawResult = explodeRows($rawResult);
            foreach ($rawResult as $io => $eachLine) {
                $eachLine = trim($eachLine);
                $rawLine = $eachLine;
                $eachLine = explode(' ', $eachLine);
                if (isset($eachLine[0])) {
                    $eachPid = $eachLine[0];
                    if (is_numeric($eachPid)) {
                        $result[$eachPid] = $rawLine;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Searches for process PIDs that match a given query string.
     *
     * This method iterates through the list of running processes and checks if the
     * process string contains the specified query. If a match is found, the PID and
     * process string are added to the result array.
     *
     * @param string $query The query string to search for within the process strings.
     * 
     * @return array
     */
    public function searchProcessPids($query = '') {
        $result = array();
        if (!empty($query)) {
            if (!empty($this->runningProcessList)) {
                foreach ($this->runningProcessList as $eachPid => $eachProcessString) {
                    if (ispos($eachProcessString, $query)) {
                        $result[$eachPid] = $eachProcessString;
                    }
                }
            }
        }
        return ($result);
    }
    /**
     * Checks if any process is available that matches the given substring.
     *
     * This method searches for process IDs (PIDs) that contain the specified substring.
     * If any matching PIDs are found, it returns true; otherwise, it returns false.
     *
     * @param string $subString The substring to search for in process names.
     * 
     * @return bool 
     */
    public function isAnyProcAvail($subString = '') {
        $result = false;
        if (!empty($subString)) {
            $filteredProcessPids = $this->searchProcessPids($subString);
            if (!empty($filteredProcessPids)) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Shortcut to fast check is some filename being somewhere in runninng process strings or not?
     *
     * @param string $fileName
     * 
     * @return bool
     */
    public function isFileInUse($fileName) {
        return ($this->isAnyProcAvail($fileName));
    }
}
