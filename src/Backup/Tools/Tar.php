<?php
/**
 * @author Ioannis Botis
 * @date 4/1/2017
 * @version: Tar.php 10:52 pm
 * @since 4/1/2017
 */

namespace Backup\Tools;

use Backup\Binary;
use Backup\FileSystem\TmpFileService;

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
     * @var \Backup\Destination\Base
     */
    private $_destination;

    /**
     * @var Binary
     */
    private $_binary;

    private $_output;

    private static $_version;

    private $_internal_settings = array();

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
        // @todo actually compare with ftp contents.
        if ($this->_destination->getType() == \Backup\Destination\Base::FTP_TYPE) {
            return self::IS_CHANGED;
        }
        $exitCode = $this->_binary->run(' --compare --file=' .
            $this->getArchiveFile($settings->number) .
            ' -C ' . $this->_main_directory->getPath() .
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
        // Get settings.
        $settings = $this->getSettings();

        // Get the new archive filename.
        $archive_file = $this->getArchiveFile($settings->number + 1);
        // Option -C goes before option -g.
        $exitCode = $this->_binary->run(' cvf ' .
            $archive_file . ' -C ' . $this->_main_directory->getPath() .
            ' ' . $this->_getExcludedPaths() .
            ' -g ' . $this->getSnapshotFile() . ' .' . DIRECTORY_SEPARATOR,
            array()
        );

        $this->_output = $this->_binary->getOutput();
        if ($exitCode == 0) {
            // if we need to synchronize between local and destination.
            if ($this->_destination->getType() == \Backup\Destination\Base::FTP_TYPE) {
                $this->synchronizeFile($this->getSnapshotFile(), DIRECTORY_SEPARATOR . $this->getSnapShotFilename());
                $this->synchronizeFile(
                    $archive_file,
                    DIRECTORY_SEPARATOR . $this->getArchiveFilename($settings->number + 1)
                );
            }
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
        $files = $this->_getFilesToRestore($time);

        foreach ($files as $file) {
            $exitCode = $this->_binary->run(
                ' xvf ' . $file . ' -g ' . '/dev/null' .
                ' -C ' . $directory->getPath(),
                array()
            );

            $this->_output = $this->_binary->getOutput();
        }
        return $exitCode;
    }

    private function _getFilesToRestore($time)
    {
        $settings = $this->getSettings();

        $restore_till_here = array_search($time, $settings->backups) + 1;

        $files = array();
        for ($i = 1; $i <= $restore_till_here; $i++) {
            $files[] = $this->getArchiveFile($i);
        }
        return $files;
    }

    /**
     * New backup available. Update settings.
     */
    protected function saveSettings()
    {
        $settings = $this->getSettings();
        $settings->number++;
        $settings->backups[] = time();
        $this->_destination->write(DIRECTORY_SEPARATOR . $this->getSettingsFile(), json_encode($settings));
    }

    /**
     * Synchronize local files to destination.
     *
     * @param $local_file
     * @param $destination_filename
     */
    protected function synchronizeFile($local_file, $destination_filename)
    {
        $contents = file_get_contents($local_file);
        $this->_destination->write($destination_filename, $contents);
    }

    /**
     * Get incremental snapshot filename.
     *
     * @return string
     */
    protected function getSnapshotFile()
    {
        if (isset($this->_internal_settings['snapshot'])) {
            return $this->_internal_settings['snapshot'];
        }
        $snapshot_file = $this->_destination->getPath() . DIRECTORY_SEPARATOR . $this->_main_directory->getBasename() . '.snar';
        if ($this->_destination->getType() == \Backup\Destination\Base::FTP_TYPE) {
            $snapshot_file_data = $this->_destination->read($this->getSnapShotFilename());
            if ($snapshot_file !== false) {
                $tmp = new TmpFileService('/tmp');
                $snapshot_file = $tmp->create($snapshot_file_data);
            }
        }
        return $this->_internal_settings['snapshot'] = $snapshot_file;
    }

    protected function getSnapShotFilename()
    {
        return $this->_main_directory->getBasename() . '.snar';
    }

    /**
     * Get archive filename.
     *
     * @param int $archive_number
     * @return string
     */
    protected function getArchiveFile($archive_number = 1)
    {
        $archive_file = $this->getArchiveFilename($archive_number);
        if ($this->_destination->getType() == \Backup\Destination\Base::FTP_TYPE) {
            $archive_file_data = $this->_destination->read($archive_file);
            if ($archive_file !== false) {
                $tmp = new TmpFileService('/tmp');
                $archive_file = $tmp->create($archive_file_data);
            }
        }
        else {
            $archive_file = $this->_destination->getPath() . DIRECTORY_SEPARATOR . $archive_file;
        }
        return $archive_file;
    }

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
