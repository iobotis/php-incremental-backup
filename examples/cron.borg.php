<?php
/**
 * @author Ioannis Botis
 * @date 17/1/2017
 * @version: cron.borg.example.php 1:20 μμ
 * @since 17/1/2017
 */

require_once('settings.php');

use Backup\Tools\Factory as ToolFactory;
use Backup\IncrementalBackup;

$settings = array(
    'path_to_backup' => $path_to_backup,
    'path_to_backup_at' => $path_to_save,
//    'passphrase' => 'abcdef',
    'exclude' => array('exclude')
);

$borg = ToolFactory::create('Borg', $settings);

echo "Version: " . $borg->getVersion() . "\n";

$backupClass = new IncrementalBackup ($borg);

if($backupClass->isChanged()) {
    $backupClass->createBackup();
}



