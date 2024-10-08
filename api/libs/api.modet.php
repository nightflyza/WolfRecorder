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
     * some predefined stuff
     */
    const PROCESS_PID = 'MOTIONDETECTOR';
    const OPTION_ENABLE = 'MODET_ENABLED';
    const FILTERED_MARK = 'motion';
    const WRAPPER = '/bin/wrapi';


    public function __construct() {
        $this->initStarDust();
        $this->initCliFF();
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
     * Runs motion detection filtering process
     *
     * @param string $filePathEnc
     * 
     * @return void
     */
    public function startMotionFilteringProcess($filePathEnc) {
        $filePath = @base64_decode($filePathEnc);
        if (!empty($filePath)) {
            if (file_exists($filePath)) {
                if ($this->process->notRunning()) {
                    $newFilePath = $this->getFilteredFileName($filePath);
                    if ($newFilePath) {
                        if (!file_exists($newFilePath)) {
                            if (!ispos($filePath, '_' . self::FILTERED_MARK . Export::RECORDS_EXT)) {
                                $this->process->start();
                                log_register('MOTION FILTERING `' . $filePath . '` STARTED');
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
     * @param string $filePathEnc
     * 
     * @return void
     */
    public function runMotionFiltering($filePathEnc) {
        $result = '';
        $filePath = @base64_decode($filePathEnc);
        if (!empty($filePath)) {
            if (file_exists($filePath)) {
                if ($this->process->notRunning()) {
                    $newFilePath = $this->getFilteredFileName($filePath);
                    if ($newFilePath) {
                        if (!file_exists($newFilePath)) {
                            if (!ispos($filePath, '_' . self::FILTERED_MARK . Export::RECORDS_EXT)) {
                                $this->process->runBackgroundProcess(self::WRAPPER . ' "modet&mdfp=' . $filePathEnc . '"', 0);
                            } else {
                                $result .= __('Something went wrong') . ' - ' . __('already filtered');
                            }
                        } else {
                            $result .=  __('Motion filtering') . ' ' . __('for this record') . ' - ' . __('already exists');
                        }
                    } else {
                        $result .= __('Something went wrong');
                    }
                } else {
                    $result .= __('Motion filtering') . ' ' . __('already running');
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
