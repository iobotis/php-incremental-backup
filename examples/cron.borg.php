<?php
/**
 * @author Ioannis Botis
 * @date 17/1/2017
 * @version: cron.borg.example.php 1:20 μμ
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

$borg->setPassPhrase('abc');

echo "Version: " . $borg->getVersion() . "\n";

$backupClass = new IncrementalBackup ($borg);

if($backupClass->isChanged()) {
    $backupClass->createBackup();
}



