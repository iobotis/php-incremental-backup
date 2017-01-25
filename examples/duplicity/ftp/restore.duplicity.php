<?php
/**
 * Example restore script.
 *
 * @author Ioannis Botis
 * @date 27/9/2016
 * @version: restore.duplicity.php 9:55 am
 * @since 27/9/2016
 */

require_once(__DIR__ . '/../../settings.php');

use Backup\Tools\Factory as ToolFactory;
use Backup\IncrementalBackup;

$settings = array(
    'path_to_backup' => $path_to_backup,
    'destination' => array(
        'type' => 'ftp',
        'host' => 'localhost',
        'username' => 'user1',
        'password' => 'abc123',
        'path' => '/'
    ),
//    'passphrase' => 'abcdef',
//    'exclude' => array('folder')
);

$duplicity = ToolFactory::create('Duplicity', $settings);

echo "Version: " . $duplicity->getVersion() . "\n";

$backupClass = new IncrementalBackup ($duplicity);

$backups = $backupClass->getAllBackups();

echo 'Backup #, time ' . "\n";
$d = new \DateTime();

$i = 1;
foreach ($backups as $time) {
    $d->setTimestamp($time);
    echo $i++ . '. ' . $d->format(\DateTime::W3C) . "\n";
}

echo "Please select version to restore. Type int to continue: ";
$handle = fopen("php://stdin", "r");
$backup_to_restore = intval(fgets($handle));

if (!in_array($backup_to_restore, range(1, $i - 1))) {
    echo 'invalid backup selected.' . "\n";
    exit;
}

// Restore last backup to this directory.
if ($backupClass->restoreTo($backups[$backup_to_restore - 1], $path_to_restore)) {
    echo 'Directory restored.' . "\n";
} else {
    echo 'Could not restore to directory.' . "\n";
}
