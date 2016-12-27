<?php
/**
 * @author Ioannis Botis
 * @date 27/9/2016
 * @version: restore.duplicity.php 12:55 am
 * @since 27/9/2016
 */

require_once('settings.php');

require_once('src/Backup/Duplicity.php');
require_once('src/Backup/IncrementalBackup.php');

use Backup\IncrementalBackup;
use Backup\Duplicity;

echo "Version: " . Duplicity::getVersion() . "\n";

$backup = new Duplicity($path_to_backup, $path_to_save);

//$backup->setPassPhrase( 'abcdef' );
$backupClass = new IncrementalBackup ($backup);

$backups = $backupClass->getAllBackups();
foreach ($backups as $time) {
    echo 'There is a backup at ' . $time . "\n";
}

// Restore last backup to this directory.
if ($backupClass->restoreTo(end($backups), $path_to_restore)) {
    echo 'Directory restored.' . "\n";
} else {
    echo 'Could not restore to directory.' . "\n";
}
