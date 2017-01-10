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

    /**
     * @var Command
     */
    private $_command;

    public function __construct(Command $duplicity)
    {
        $this->_command = $duplicity;
    }

    /**
     * @return bool
     * @throws \Backup\RuntimeException
     */
    public function isChanged()
    {
        $status = $this->_command->verify();
        // Use verify to compare data between last backup and current data.
        if ($status == Command::NO_CHANGES) {
            return false;
        } elseif ($status == Command::IS_CHANGED) {
            return true;
        } elseif ($status == Command::NO_BACKUP_FOUND) {
            return true;
        }
        throw new \Backup\RuntimeException('Corrupt data');
    }

    public function createBackup($full = false)
    {
        $this->_command->execute($full);
    }

    public function getAllBackups()
    {
        return $this->_command->getAllBackups();
    }

    public function restoreTo($time, $directory)
    {
        try {
            $exitCode = $this->_command->restore($time, $directory);
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