<?php
/**
 * @author Ioannis Botis
 * @date 23/9/2016
 * @version: cron.duplicity.php 11:59 am
 * @since 23/9/2016
 */

require_once( 'IncrementalBackup.php' );

echo "Version: " . Duplicity::getVersion() . "\n";

$backup = new Duplicity( '/path/to/backup', '/path/to/save' );

//$backup->setPassPhrase( 'abcdef' );

if( $backup->verify()  != 0 ) {
    // back me up.
    echo 'Back up initiated' . "\n";
    $backup->execute();
}
else {
    echo 'No need to backup.' . "\n";
}

$backupClass = new IncrementalBackup ( $backup );

$backups = $backupClass->getAllBackups();
foreach ($backups as $time) {
    echo 'There is a backup at ' . $time . "\n";
}

if( $backupClass->isChanged() ) {
    // back me up.
    echo 'Back up initiated' . "\n";
    $backupClass->createBackup();
}
else {
    echo 'No need to backup.' . "\n";
}