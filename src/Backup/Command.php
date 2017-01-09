<?php
/**
 * @author Ioannis Botis
 * @date 28/12/2016
 * @version: Command.php 7:01 pm
 * @since 28/12/2016
 */

namespace Backup;

/**
 * Interface Command exposes the main function needed to create an incremental backup class.
 *
 * @package Backup
 */
interface Command
{
    /**
     * Verify return values.
     */
    const NO_CHANGES = 0;
    const IS_CHANGED = 1;
    const NO_BACKUP_FOUND = 2;
    const CORRUPT_DATA = 3;

    /**
     * Test whether there are any new changes to the files since the last backup.
     * Also verify if the backup is not corrupted.
     *
     * @return integer one of the constants NO_CHANGES,IS_CHANGED or CORRUPT_DATA.
     */
    public function verify();

    /**
     * Backup our data.
     *
     * @return integer status code of the command executed.
     */
    public function execute();

    /**
     * Get a list of all backups taken.
     *
     * @return string[] array of unix timestamps of backups taken.
     */
    public function getAllBackups();

    /**
     * Restore our data to a destination folder.
     *
     * @param integer $time unix timestamp to restore at.
     * @param string $directory the path of the duplicity files.
     * @return integer
     */
    public function restore($time, $directory);
}