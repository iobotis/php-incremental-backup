<?php
/**
 * @author Ioannis Botis
 * @date 4/1/2017
 * @version: Tar.php 10:52 pm
 * @since 4/1/2017
 */

namespace Backup\Tools;

use Backup\Binary;

/**
 * Class Tar
 * Class wrapper for tar command.
 *
 * @package Backup
 */
class Tar implements Command
{

    const CMD = 'tar';
    const CMD_SUFIX = '2>/dev/null';

    /**
     * @var \Backup\FileSystem\Source the main directory to backup.
     */
    private $_main_directory;
    /**
     * @var string[] subdirectories of the main directory we want to exclude, eg cache, temp.
     */
    private $_excluded_directories;

    /**
     * @var \Backup\FileSystem\Destination
     */
    private $_destination;

    /**
     * @var Binary
     */
    private $_binary;

    private $_output;

    private static $_version;

    /**
     * Tar constructor.
     *
     * @param string $directory the path to the directory to backup.
     * @param string $destination the path to the directory to keep the backup files.
     */
    public function __construct(
        \Backup\FileSystem\Source $directory,
        \Backup\Destination\Base $destination,
        Binary $binary
    ) {
        if (!$directory->exists()) {
            throw new \Backup\Exception\InvalidArgumentException('Tar backup path is invalid');
        }
        $this->_binary = $binary;
        $this->_main_directory = $directory;
        $this->_destination = $destination;
    }

    /**
     * Check if duplicity is installed.
     * @return bool
     */
    public function isInstalled()
    {
        $exitCode = $this->_binary->run(' --version',
            array()
        );

        if ($exitCode) {
            return false;
        }
        return true;
    }

    /**
     * Returns the version of duplicity.
     *
     * @return string
     */
    public function getVersion()
    {
        if (isset(self::$_version)) {
            return self::$_version;
        }
        $exitCode = $this->_binary->run(' --version',
            array()
        );

        $output = $this->_binary->getOutput();
        $output = explode(' ', $output[0]);
        return self::$_version = trim(str_replace('tar', '', end($output)));
    }

    /**
     * Exclude subdirectories from backup.
     * Multiple level paths supported eg. ["sudir1", "subdir2/dir"].
     * Not full path, but relative paths.
     * If a subdirectory does not exist, it will be ignored.
     *
     * @param array $subDirs an array of subdirectories to exclude.
     */
    public function setExludedSubDirectories(array $subDirs)
    {
        $this->_excluded_directories = $subDirs;
    }

    /**
     * Get the backup settings, # of backups and backup unix timestamps.
     *
     * @return mixed|object
     */
    public function getSettings()
    {
        //$settings_file = $this->_destination->getPath() . DIRECTORY_SEPARATOR . $this->getSettingsFile();
        $settings_file = $this->_destination->read(DIRECTORY_SEPARATOR . $this->getSettingsFile());
        if ($settings_file) {
            return json_decode($settings_file);
        } // first time to backup.
        else {
            return (object)array(
                "number" => 0,
                "backups" => array()
            );
        }
    }

    /**
     * Get the settings file name.
     *
     * @return string
     */
    protected function getSettingsFile()
    {
        return $this->_main_directory->getBasename() . '.json';
    }

    public function verify()
    {
        if (!$this->_destination->canAccess()) {
            return self::CORRUPT_DATA;
        }

        $settings = $this->getSettings();
        // no backups found.
        if ($settings->number == 0) {
            return self::NO_BACKUP_FOUND;
        }
        $exitCode = $this->_binary->run(' --compare --file=' . $this->_destination->getPath() . DIRECTORY_SEPARATOR .
            $this->getArchiveFilename($settings->number) . ' -C ' . $this->_main_directory->getPath() .
            ' ' . $this->_getExcludedPaths() .
            ' .' . DIRECTORY_SEPARATOR,
            array()
        );

        if ($exitCode == 0) {
            return self::NO_CHANGES;
        } elseif ($exitCode == 1) {
            return self::IS_CHANGED;
        }
        return self::CORRUPT_DATA;

        // @todo check archives exist, main file.
        // @todo compare with directory.
        //self::exec(self::CMD . '');
    }

    public function execute()
    {
        $settings = $this->getSettings();
        // Option -C goes before option -g.
        $exitCode = $this->_binary->run(' cvf ' . $this->_destination->getPath() . DIRECTORY_SEPARATOR .
            $this->getArchiveFilename($settings->number + 1) . ' -C ' . $this->_main_directory->getPath() .
            ' ' . $this->_getExcludedPaths() .
            ' -g ' . $this->_destination->getPath() . DIRECTORY_SEPARATOR .
            $this->getSnapshotFileName() . ' .' . DIRECTORY_SEPARATOR,
            array()
        );

        $this->_output = $this->_binary->getOutput();
        if ($exitCode == 0) {
            $this->saveSettings();
        }
        return $exitCode;
    }

    public function getAllBackups()
    {
        $settings = $this->getSettings();
        return $settings->backups;
    }

    public function restore($time, \Backup\FileSystem\Folder $directory)
    {
        $settings = $this->getSettings();

        $restore_till_here = array_search($time, $settings->backups) + 1;

        for ($i = 1; $i <= $restore_till_here; $i++) {
            $exitCode = $this->_binary->run(
                ' xvf ' . $this->_destination->getPath() . DIRECTORY_SEPARATOR .
                $this->getArchiveFilename($i) . ' -g ' . '/dev/null' .
                ' -C ' . $directory->getPath(),
                array()
            );

            $this->_output = $this->_binary->getOutput();
        }
        return $exitCode;
    }

    /**
     * New backup available. Update settings.
     */
    protected function saveSettings()
    {
        $settings = $this->getSettings();
        $settings->number++;
        $settings->backups[] = time();
        file_put_contents($this->_destination->getPath() . DIRECTORY_SEPARATOR . $this->getSettingsFile(),
            json_encode($settings));
    }

    /**
     * Get incremental snapshot filename.
     *
     * @return string
     */
    protected function getSnapshotFileName()
    {
        return $this->_main_directory->getBasename() . '.snar';
    }

    /**
     * Get archive filename.
     *
     * @param int $archive_number
     * @return string
     */
    protected function getArchiveFilename($archive_number = 1)
    {
        return $this->_main_directory->getBasename() . '.' . $archive_number . '.tar';
    }

    /**
     *
     * @return string
     */
    private function _getExcludedPaths()
    {
        if (empty($this->_excluded_directories)) {
            return '';
        } else {
            return " --anchored --exclude=." . DIRECTORY_SEPARATOR .
            implode(
                " --exclude=." . DIRECTORY_SEPARATOR,
                $this->_excluded_directories
            ) . ' ';
        }
    }

    public function getOutput()
    {
        return $this->_output;
    }
}