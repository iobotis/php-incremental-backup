<?php
/**
 * @author Ioannis Botis
 * @date 5/1/2017
 * @version: cron.tar.php 10:45 pm
 * @since 5/1/2017
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
    //'exclude' => array('exclude', 'exclude1')
);

$tar = ToolFactory::create('Tar', $settings);

echo "Version: " . $tar->getVersion() . "\n";

$backupClass = new IncrementalBackup ($tar);

if ($backupClass->isChanged()) {
    // back me up.
    echo 'Back up initiated' . "\n";
    $backupClass->createBackup();
} else {
    echo 'No need to backup.' . "\n";
}