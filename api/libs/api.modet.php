<?php

/**
 * Basic records motion detection/filtering implementation
 */
class MoDet {

    /**
     * CLI abstraction layer
     *
     * @var object
     */
    protected $cliFF = '';
    /**
     * Stardust instance
     *
     * @var object
     */
    protected $process = '';


    /**
     * HyprSpace object instance for user saved records storage
     *
     * @var object
     */
    protected $hyprSpace = '';

    /**
     * some predefined stuff
     */
    const PROCESS_PID = 'MOTIONDETECTOR';
    const OPTION_ENABLE = 'MODET_ENABLED';
    const FILTERED_MARK = 'motion';
    const WRAPPER = '/bin/wrapi';


    public function __construct() {
        $this->initStarDust();
        $this->initCliFF();
        $this->initHyprSpace();
    }

    /**
     * Inits CLI abstraction layer instance
     *
     * @return void
     */
    protected function initCliFF() {
        $this->cliFF = new CliFF();
    }

    /**
     * Inits StarDust process manager for further usage
     *
     * @return void
     */
    protected function initStarDust() {
        $this->process = new StarDust(self::PROCESS_PID);
    }

    /**
     * Inits HyprSpace instance for further usage
     *
     * @return void
     */
    protected function initHyprSpace() {
        $this->hyprSpace = new HyprSpace();
    }

    /**
     * Returns new recording filename with filtered motion
     *
     * @param string $filePath
     * 
     * @return string
     */
    protected function getFilteredFileName($filePath) {
        $result = '';
        if (!empty($filePath)) {
            $result .= str_replace(Export::RECORDS_EXT, '_' . self::FILTERED_MARK . Export::RECORDS_EXT, $filePath);
        }
        return ($result);
    }

    /**
     * Returns current user recordings path
     *
     * @return void
     */
    protected function getUserRecordingsDir() {
        $result = '';
        $userLogin = whoami();
        if (!empty($userLogin)) {
            $fullUserPath = $this->hyprSpace->getPathRecords() . $userLogin;
            if (file_exists($fullUserPath)) {
                $result = $fullUserPath . '/'; //with ending slash
            }
        }
        return ($result);
    }

    /**
     * Runs motion detection filtering process
     *
     * @param string $filePathEnc
     * @param int $threshold
     * @param int $timeScale
     * 
     * @return void
     */
    public function startMotionFilteringProcess($filePathEnc, $threshold = 0, $timeScale = 0) {
        $filePath = @base64_decode($filePathEnc);
        $threshold = ubRouting::filters($threshold, 'int');
        $timeScale = ubRouting::filters($timeScale, 'int');
        if (!empty($filePath)) {
            if (file_exists($filePath)) {
                if ($this->process->notRunning()) {
                    $newFilePath = $this->getFilteredFileName($filePath);
                    if ($newFilePath) {
                        if (!file_exists($newFilePath)) {
                            if (!ispos($filePath, '_' . self::FILTERED_MARK . Export::RECORDS_EXT)) {
                                $this->process->start();
                                log_register('MOTION FILTERING `' . $filePath . '` STARTED');
                                if ($threshold and $timeScale) {
                                    $this->cliFF->setMoDetParams($threshold, $timeScale);
                                }
                                $command = $this->cliFF->getFFmpegPath() . ' -i ' . $filePath . ' ' . $this->cliFF->getMoDetOpts() . ' ' . $newFilePath;
                                shell_exec($command);
                                log_register('MOTION FILTERING `' . $newFilePath . '` FINISHED');
                                $this->process->stop();
                                //few checks
                                if (file_exists($newFilePath)) {
                                    if (filesize($newFilePath) < 1024) {
                                        rcms_delete_files($newFilePath);
                                        log_register('MOTION FILTERING `' . $newFilePath . '` FAILED CORRUPTED');
                                    }
                                } else {
                                    log_register('MOTION FILTERING `' . $newFilePath . '` FAILED NOT EXISTS');
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Schedules background motion detection process for background execution
     *
     * @param string $fileNameEnc
     * @param int $threshold
     * @param int $timeScale
     * 
     * @return void
     */
    public function runMotionFiltering($fileNameEnc, $threshold = 0, $timeScale = 0) {
        $result = '';
        $userRecordingsDir = $this->getUserRecordingsDir();
        $fileName = @base64_decode($fileNameEnc);
        $threshold = ubRouting::filters($threshold, 'int');
        $timeScale = ubRouting::filters($timeScale, 'int');
        if (!empty($fileName)) {
            $filePath = $userRecordingsDir . $fileName;
            $newFileName = $this->getFilteredFileName($fileName);
            $newFilePath = $userRecordingsDir . $newFileName;
            if (file_exists($filePath)) {
                if (!file_exists($newFilePath)) {
                    if ($this->process->notRunning()) {
                        if ($newFilePath) {
                            if (!ispos($filePath, '_' . self::FILTERED_MARK . Export::RECORDS_EXT)) {
                                $bgUrl = '"';
                                $bgUrl .= 'modet&mdfp=' . base64_encode($filePath);
                                if ($threshold and $timeScale) {
                                    $bgUrl .= '&th=' . $threshold . '&ts=' . $timeScale;
                                }
                                $bgUrl .= '"';
                                $procCmd = self::WRAPPER . ' ' . $bgUrl;
                                $this->process->runBackgroundProcess($procCmd, 0);
                                log_register('MOTION FILTERING `' . $newFileName . '` SENS `' . $threshold . '` TSCALE `' . $timeScale . '` SCHEDULED');
                            } else {
                                $result .= __('Something went wrong') . ' - ' . __('Record') . ' ' . __('already filtered');
                            }
                        } else {
                            $result .= __('Something went wrong');
                        }
                    } else {
                        $result .= __('Motion filtering service is busy at this moment') . '. ' . __('Please try again later') . '.';
                    }
                } else {
                    $result .=  __('Motion filtering') . ' ' . __('for this record') . ' - ' . __('already exists');
                }
            } else {
                $result .= __('File not exists');
            }
        } else {
            $result .= __('Path') . ' ' . __('is empty');
        }
        return ($result);
    }


    /**
     * Just renders successfull motion detection schedule notification and confirmation
     * 
     * 
     * @return string
     */
    public function renderScheduledNotify() {
        $result = '';
        $notification = '';
        $notification .= wf_tag('center') . wf_img('skins/checked.png') . wf_tag('center', true);
        $notification .= wf_delimiter(0);
        $notification .= __('Motion filtering for your video is running in the background. It may take some time.') . '.';
        $notification .= wf_delimiter();
        $notification .= wf_tag('center') . wf_Link(Export::URL_RECORDS, __('Got it') . '!', true, 'confirmagree') . wf_tag('center', true);
        $result .= wf_modalOpenedAuto(__('Motion filtering'), $notification);
        return ($result);
    }
}
