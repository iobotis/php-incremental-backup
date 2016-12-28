<?php
/**
 * @author Ioannis Botis
 * @date 28/12/2016
 * @version: Command.php 1:01 μμ
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
     * Test whether there are any new changes to the files since the last backup.
     * Also verify if the backup is not corrupted.
     *
     * @return boolean true if they are any changes, false otherwise.
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