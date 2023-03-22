<?php

/**
 * Database backup
 */
if (ubRouting::get('action') == 'backupdb') {
	$binPaths=$ubillingConfig->getBinpaths();
	$altCfg=$ubillingConfig->getAlter();
    if ($binPaths['MYSQLDUMP_PATH']) {
        $backpath = zb_BackupDatabase(true);
        if (@$altCfg['BACKUPS_MAX_AGE']) {
            zb_BackupsRotate($altCfg['BACKUPS_MAX_AGE']);
        }
    } else {
        die('ERROR:NO_MYSQLDUMP_PATH');
    }
    die('OK:BACKUPDB ' . $backpath);
}