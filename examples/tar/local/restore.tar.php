<?php
/**
 * @author Ioannis Botis
 * @date 9/1/2017
 * @version: restore.tar.php 8:46 pm
 * @since 9/1/2017
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
);

$tar = ToolFactory::create('Tar', $settings);

echo "Version: " . $tar->getVersion() . "\n";

$backupClass = new IncrementalBackup ($tar);

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
