<?php
/**
 * @author Ioannis Botis
 * @date 23/9/2016
 * @version: cron.duplicity.php 11:59 am
 * @since 23/9/2016
 */

require_once('settings.php');

require_once('src/Backup/Duplicity.php');
require_once('src/Backup/IncrementalBackup.php');

use Backup\IncrementalBackup;
use Backup\Duplicity;

echo "Version: " . Duplicity::getVersion() . "\n";

$backup = new Duplicity( $path_to_backup, $path_to_save );

//$backup->setPassPhrase( 'abcdef' );

$backupClass = new IncrementalBackup ($backup);

$backups = $backupClass->getAllBackups();
foreach ($backups as $time) {
    echo 'There is a backup at ' . $time . "\n";
}

if ($backupClass->isChanged()) {
    // back me up.
    echo 'Back up initiated' . "\n";
    $backupClass->createBackup();
} else {
    echo 'No need to backup.' . "\n";
}