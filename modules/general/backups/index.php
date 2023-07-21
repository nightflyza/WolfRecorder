<?php

if (cfr('BACKUP')) {
    set_time_limit(0);
    $alterConf = $ubillingConfig->getAlter();
    $binPathsConf=$ubillingConfig->getBinpaths();

    if (!ubRouting::checkGet(array('restore'))) {
        if (ubRouting::post('createbackup')) {
            if (ubRouting::post('imready')) {
                if (!empty($binPathsConf['MYSQLDUMP_PATH'])) {
                    //run system mysqldump command
                    zb_BackupDatabase();
                } else {
                    show_error(__('You missed an important option') . ': MYSQLDUMP_PATH');
                }
            } else {
                show_error(__('You are not mentally prepared for this'));
            }
        }

//downloading mysql dump
        if (ubRouting::checkGet('download')) {
            if (cfr('ROOT')) {
                $filePath = base64_decode(ubRouting::get('download'));
                zb_DownloadFile($filePath);
            } else {
                show_error(__('Access denied'));
            }
        }


//deleting dump
        if (ubRouting::checkGet('deletedump')) {
            if (cfr('ROOT')) {
                $deletePath = base64_decode(ubRouting::get('deletedump'));
                if (file_exists($deletePath)) {
                    rcms_delete_files($deletePath);
                    log_register('BACKUP DELETE `' . $deletePath . '`');
                    ubRouting::nav('?module=backups');
                } else {
                    show_error(__('Not existing item'));
                }
            } else {
                show_error(__('Access denied'));
            }
        }

        function web_AvailableDBBackupsList() {
            $backupsPath = DATA_PATH . 'backups/sql/';
            $availbacks = rcms_scandir($backupsPath);
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('No existing DB backups here'), 'warning');
            if (!empty($availbacks)) {
                $cells = wf_TableCell(__('Creation date'));
                $cells .= wf_TableCell(__('Size'));
                $cells .= wf_TableCell(__('Filename'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($availbacks as $eachDump) {
                    if (is_file($backupsPath . $eachDump)) {
                        $fileDate = filectime($backupsPath . $eachDump);
                        $fileDate = date("Y-m-d H:i:s", $fileDate);
                        $fileSize = filesize($backupsPath . $eachDump);
                        $fileSize = wr_convertSize($fileSize);
                        $encodedDumpPath = base64_encode($backupsPath . $eachDump);
                        $downloadLink = wf_Link('?module=backups&download=' . $encodedDumpPath, $eachDump, false, '');
                        $actLinks = wf_JSAlert('?module=backups&deletedump=' . $encodedDumpPath, web_delete_icon(), __('Removing this may lead to irreparable results')) . ' ';
                        $actLinks .= wf_Link('?module=backups&download=' . $encodedDumpPath, wf_img('skins/icon_download.png', __('Download')), false, '');
                        $actLinks .= wf_JSAlert('?module=backups&restore=true&restoredump=' . $encodedDumpPath, wf_img('skins/icon_restoredb.png', __('Restore DB')), __('Are you serious'));

                        $cells = wf_TableCell($fileDate);
                        $cells .= wf_TableCell($fileSize);
                        $cells .= wf_TableCell($downloadLink);
                        $cells .= wf_TableCell($actLinks);
                        $rows .= wf_TableRow($cells, 'row5');
                    }
                }
                $result = wf_TableBody($rows, '100%', '0', 'sortable resp-table');
            }

            return ($result);
        }

        show_window(__('Create backup'), web_BackupForm());
        show_window(__('Available database backups'), web_AvailableDBBackupsList());
    } else {
        //database restoration functionality
        if (cfr('ROOT')) {
            if (!empty($binPathsConf['MYSQL_PATH'])) {
                if (ubRouting::checkGet(array('restoredump'))) {
                    $mysqlConf = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');
                    $restoreFilename = base64_decode(ubRouting::get('restoredump'));
                    if (file_exists($restoreFilename)) {

                        if (!ubRouting::post('lastchanceok')) {
                            $lastChanceInputs = __('Restoring a database from a dump, completely and permanently destroy your current database. Think again if you really want it.');
                            $lastChanceInputs .= wf_tag('br');
                            $lastChanceInputs .= __('Filename') . ': ' . $restoreFilename;
                            $lastChanceInputs .= wf_tag('br');
                            $lastChanceInputs .= wf_CheckInput('lastchanceok', __('I`m ready'), true, false);
                            $lastChanceInputs .= wf_Submit(__('Restore DB'));
                            $lastChanceForm = wf_Form('', 'POST', $lastChanceInputs, 'glamour');
                            show_window(__('Warning'), $lastChanceForm);
                            show_window('', wf_BackLink('?module=backups', __('Back'), true, 'ubButton'));
                        } else {
                            $restoreCommand = $binPathsConf['MYSQL_PATH'] . ' --host ' . $mysqlConf['server'] . ' -u ' . $mysqlConf['username'] . ' -p' . $mysqlConf['password'] . ' ' . $mysqlConf['db'] . ' --default-character-set=utf8 < ' . $restoreFilename . ' 2>&1';
                            $restoreResult = shell_exec($restoreCommand);
                            if (ispos($restoreResult, 'command line interface')) {
                                $restoreResult = '';
                            }
                            if (empty($restoreResult)) {
                                show_success(__('Success') . '! ' . __('Database') . ' ' . $mysqlConf['db'] . ' ' . __('is restored to server') . ' ' . $mysqlConf['server']);
                            } else {
                                show_error(__('Something went wrong'));
                                show_window(__('Result'), $restoreResult);
                            }
                            show_window('', wf_BackLink('?module=backups'));
                        }
                    } else {
                        show_error(__('Strange exeption') . ': NOT_EXISTING_DUMP_FILE');
                    }
                } else {
                    show_error(__('Strange exeption') . ': GET_NO_DUMP_FILENAME');
                }
            } else {
                show_error(__('You missed an important option') . ': MYSQL_PATH');
            }
        } else {
            show_error(__('You cant control this module'));
        }
        //////////////////////////////////////////////////////
    }
} else {
    show_error(__('You cant control this module'));
}
