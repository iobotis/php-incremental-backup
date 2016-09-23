<?php
/**
 * @author Ioannis Botis
 * @date 23/9/2016
 * @version: cron.duplicity.php 11:59 am
 * @since 23/9/2016
 */

require_once( 'Duplicity.php' );

echo "Version: " . Duplicity::getVersion() . "\n";

$backup = new Duplicity( '/path/to/backup', '/path/to/save' );

if( $backup->verify()  != 0 ) {
    // back me up.
    $backup->execute();
}
else {
    echo 'No need to backup.' . "\n";
}

$backup->getCollectionStatus();
echo( implode( "\n", $backup->getOutput() ) );