<?php

/**
 * Returns database backup creation form
 * 
 * @return string
 */
function web_BackupForm() {
    $backupinputs = __('This will create a backup copy of all tables in the database') . wf_tag('br');
    $backupinputs .= wf_HiddenInput('createbackup', 'true');
    $backupinputs .= wf_CheckInput('imready', 'I`m ready', true, false);
    $backupinputs .= wf_Submit('Create');
    $form = wf_Form('', 'POST', $backupinputs, 'glamour');

    return($form);
}

/**
 * Converts bytes into human-readable values like Kb, Mb, Gb...
 * 
 * @param int $fs
 * @param string $traffsize
 * 
 * @return string
 */
function stg_convert_size($fs, $traffsize = 'float') {
    if ($traffsize == 'float') {
        if ($fs >= (1073741824 * 1024))
            $fs = round($fs / (1073741824 * 1024) * 100) / 100 . " Tb";
        elseif ($fs >= 1073741824)
            $fs = round($fs / 1073741824 * 100) / 100 . " Gb";
        elseif ($fs >= 1048576)
            $fs = round($fs / 1048576 * 100) / 100 . " Mb";
        elseif ($fs >= 1024)
            $fs = round($fs / 1024 * 100) / 100 . " Kb";
        else
            $fs = $fs . " b";
        return ($fs);
    }

    if ($traffsize == 'b') {
        return ($fs);
    }

    if ($traffsize == 'Kb') {
        $fs = round($fs / 1024 * 100) / 100 . " Kb";
        return ($fs);
    }

    if ($traffsize == 'Mb') {
        $fs = round($fs / 1048576 * 100) / 100 . " Mb";
        return ($fs);
    }
    if ($traffsize == 'Gb') {
        $fs = round($fs / 1073741824 * 100) / 100 . " Gb";
        return ($fs);
    }

    if ($traffsize == 'Tb') {
        $fs = round($fs / (1073741824 * 1024) * 100) / 100 . " Tb";
        return ($fs);
    }
}

/**
 * Dumps database to file and returns filename
 * 
 * @param bool $silent
 * 
 * @return string
 */
function zb_BackupDatabase($silent = false) {
    global $ubillingConfig;
    $backname = '';
    $backupProcess = new StarDust('BACKUPDB');
    if ($backupProcess->notRunning()) {
        $backupProcess->start();
        $alterConf = $ubillingConfig->getAlter();
        $mysqlConf = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');

        $backname = DATA_PATH . 'backups/sql/wolfrecorder-' . date("Y-m-d_H_i_s", time()) . '.sql';
        $command = $alterConf['MYSQLDUMP_PATH'] . ' --host ' . $mysqlConf['server'] . ' -u ' . $mysqlConf['username'] . ' -p' . $mysqlConf['password'] . ' ' . $mysqlConf['db'] . ' > ' . $backname;
        shell_exec($command);

        if (!$silent) {
            show_success(__('Backup saved') . ': ' . $backname);
        }

        log_register('BACKUP CREATE `' . $backname . '`');
        $backupProcess->stop();
    } else {
        log_register('BACKUP ALREADY RUNNING SKIPPED');
    }
    return ($backname);
}

/**
 * Shows database cleanup form
 * 
 * @return string
 */
function web_DBCleanupForm() {
    $cleanupData = $oldLogs + $oldDetailstat;
    $result = '';
    $totalRows = 0;
    $totalSize = 0;
    $totalCount = 0;

    $cells = wf_TableCell(__('Table name'));
    $cells .= wf_TableCell(__('Rows'));
    $cells .= wf_TableCell(__('Size'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($cleanupData)) {
        foreach ($cleanupData as $io => $each) {
            $cells = wf_TableCell($each['name']);
            $cells .= wf_TableCell($each['rows']);
            $cells .= wf_TableCell(stg_convert_size($each['size']), '', '', 'sorttable_customkey="' . $each['size'] . '"');
            $actlink = wf_JSAlert("?module=backups&tableclean=" . $each['name'], web_delete_icon(), 'Are you serious');
            $cells .= wf_TableCell($actlink);
            $rows .= wf_TableRow($cells, 'row5');
            $totalRows = $totalRows + $each['rows'];
            $totalSize = $totalSize + $each['size'];
            $totalCount = $totalCount + 1;
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    $result .= wf_tag('b') . __('Total') . ': ' . $totalCount . ' / ' . $totalRows . ' / ' . stg_convert_size($totalSize) . wf_tag('b', true);

    return ($result);
}

/**
 * This function perform removing of files and directories
 * 
 * @param string $file
 * @param bool $recursive
 * 
 * @return bool
 */
function rcms_delete_files($file, $recursive = false) {
    if ($recursive && is_dir($file)) {
        $els = rcms_scandir($file, '', '', true);
        foreach ($els as $el) {
            if ($el != '.' && $el != '..') {
                rcms_delete_files($file . '/' . $el, true);
            }
        }
    }
    if (is_dir($file)) {
        return rmdir($file);
    } else {
        return unlink($file);
    }
}
