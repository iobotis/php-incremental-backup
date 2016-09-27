<?php
require_once 'Duplicity.php';
/**
 * @author Ioannis Botis
 * @date 23/9/2016
 * @version: IncrementalBackup.php 1:42 μμ
 * @since 23/9/2016
 */
class IncrementalBackup
{

    private $_duplicity;

    public function __construct( Duplicity $duplicity )
    {
        $this->_duplicity = $duplicity;
    }

    public function isChanged() {
        // Use verify to compare data between last backup and current data.
        if( $this->_duplicity->verify() == 1 ) {
            return true;
        }
        return false;
    }

    public function createBackup( $full = false ) {
        $this->_duplicity->execute( $full );
    }

    public function getAllBackups() {
        $this->_duplicity->getCollectionStatus();
        $output = $this->_duplicity->getOutput();

        $backups = array();
        foreach ( $output as $line ) {
            if( preg_match( "/(Full|Incremental)[\s]+(.*)[\s]{10}/", $line, $results) ) {
                $backups[] = trim( $results[2] );
            }
        }
        return $backups;
    }

    public function restoreTo( $time, $directory ) {
        $d = new DateTime( $time );
        $time = $d->format( DateTime::W3C );
        try {
            $exitCode = $this->_duplicity->restore( $time, $directory );
        }
        catch ( Exception $e ) {
            return false;
        }

        // Duplicity returned an non zero code, there was an error.
        if( $exitCode ) {
            return false;
        }
        return true;
    }
}