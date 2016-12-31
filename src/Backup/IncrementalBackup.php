<?php
namespace Backup;

/**
 * @author Ioannis Botis
 * @date 23/9/2016
 * @version: IncrementalBackup.php 7:42 pm
 * @since 23/9/2016
 */
class IncrementalBackup
{

    private $_duplicity;

    public function __construct(Command $duplicity)
    {
        $this->_duplicity = $duplicity;
    }

    public function isChanged()
    {
        // Use verify to compare data between last backup and current data.
        if ($this->_duplicity->verify() == 0) {
            return false;
        }
        return true;
    }

    public function createBackup($full = false)
    {
        $this->_duplicity->execute($full);
    }

    public function getAllBackups()
    {
        return $this->_duplicity->getAllBackups();
    }

    public function restoreTo($time, $directory)
    {
        try {
            $exitCode = $this->_duplicity->restore($time, $directory);
        } catch (Exception $e) {
            return false;
        }

        // Duplicity returned an non zero code, there was an error.
        if ($exitCode) {
            return false;
        }
        return true;
    }
}