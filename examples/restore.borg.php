<?php
/**
 * @author Ioannis Botis
 * @date 17/1/2017
 * @version: restore.borg.php 7:22 μμ
 * @since 17/1/2017
 */

require_once('settings.php');

use Backup\Binary;
use Backup\Borg;
use Backup\IncrementalBackup;

$settings = array(
    'path_to_backup' => $path_to_backup,
    'path_to_backup_at' => $path_to_save,
//    'passphrase' => 'abcdef',
//    'exclude' => array('folder')
);

$binary = new Binary('borg');

$borg = new Borg($settings['path_to_backup'], $settings['path_to_backup_at'], $binary);

echo "Version: " . $borg->getVersion() . "\n";

$borg->verify();

$backups = $borg->getAllBackups();

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
if ($borg->restore($backups[$backup_to_restore - 1], $path_to_restore) == 0) {
    echo 'Directory restored.' . "\n";
} else {
    echo 'Could not restore to directory.' . "\n";
}
