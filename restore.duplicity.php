<?php
/**
 * @author Ioannis Botis
 * @date 27/9/2016
 * @version: restore.duplicity.php 12:55 am
 * @since 27/9/2016
 */

require_once( 'IncrementalBackup.php' );

echo "Version: " . Duplicity::getVersion() . "\n";

$backup = new Duplicity( '/path/to/backup', '/path/to/save' );

//$backup->setPassPhrase( 'abcdef' );
$backupClass = new IncrementalBackup ( $backup );

$backups = $backupClass->getAllBackups();
foreach ($backups as $time) {
    echo 'There is a backup at ' . $time . "\n";
}

// Restore last backup to this directory.
if( $backupClass->restoreTo( end( $backups ), '/path/to/restore' ) ) {
    echo 'Directory restored.' . "\n";
}
else {
    echo 'Could not restore to directory.' . "\n";
}
