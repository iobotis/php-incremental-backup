<?php
/**
 * Example backup script.
 *
 * @author Ioannis Botis
 * @date 23/9/2016
 * @version: cron.duplicity.php 8:59 am
 * @since 23/9/2016
 */

require_once(__DIR__ . '/../../settings.php');

use Backup\Tools\Factory as ToolFactory;
use Backup\IncrementalBackup;

$settings = array(
    'path_to_backup' => $path_to_backup,
    'destination' => array(
        'type' => 'local',
        'path' => $path_to_save
    ),
//    'passphrase' => 'abcdef',
//    'exclude' => array('folder')
);

$duplicity = ToolFactory::create('Duplicity', $settings);
echo "Version: " . $duplicity->getVersion() . "\n";

$backupClass = new IncrementalBackup ($duplicity);

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