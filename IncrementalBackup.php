<?php
require_once 'Duplicity.php';
/**
 * @author Ioannis Botis <ioannis.botis@interactivedata.com>
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
        if( $this->_duplicity->verify() != 0 ) {
            return true;
        }
        return false;
    }

    public function createBackup() {
        $this->_duplicity->execute();
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

    }
}