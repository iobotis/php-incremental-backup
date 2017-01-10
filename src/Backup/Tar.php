<?php
/**
 * @author Ioannis Botis
 * @date 4/1/2017
 * @version: Tar.php 10:52 pm
 * @since 4/1/2017
 */

namespace Backup;

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
     * @var string the main directory to backup.
     */
    private $_main_directory;
    /**
     * @var string[] subdirectories of the main directory we want to exclude, eg cache, temp.
     */
    private $_excluded_directories;

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
    public function __construct($directory, $destination, Binary $binary)
    {
        $this->_binary = $binary;
        $this->_setMainDirectory($directory);
        $this->_destination = $destination;
    }

    private function _setMainDirectory($directory)
    {
        if (!$this->isInstalled()) {
            throw new \Backup\Exception\BinaryNotFoundException('Tar is not installed');
        }
        if (!$this->directoryExists($directory)) {
            throw new \Backup\Exception\InvalidArgumentException('Tar backup path is invalid');
        }
        $this->_main_directory = $directory;
    }

    /**
     * @param string $directory
     * @return bool
     */
    protected function directoryExists($directory)
    {
        return is_dir($directory);
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
     * @todo check if it only excludes folder relative to the root.
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
        if (file_exists($this->_destination . DIRECTORY_SEPARATOR . $this->getSettingsFile())) {
            return json_decode(file_get_contents($this->_destination . DIRECTORY_SEPARATOR . $this->getSettingsFile()));
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
        return $this->getMainDirectoryBasename() . '.json';
    }

    public function verify()
    {
        if (!is_readable($this->_destination)) {
            return self::CORRUPT_DATA;
        }

        $settings = $this->getSettings();
        // no backups found.
        if ($settings->number == 0) {
            return self::NO_BACKUP_FOUND;
        }
        $exitCode = $this->_binary->run(' --compare --file=' . $this->_destination . DIRECTORY_SEPARATOR .
            $this->getArchiveFilename($settings->number) . ' -C ' . $this->getMainDirectoryName() .
            ' ' . $this->_getExcludedPaths() .
            ' ' . $this->getMainDirectoryBasename(),
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
        $exitCode = $this->_binary->run(' cvf ' . $this->_destination . DIRECTORY_SEPARATOR .
            $this->getArchiveFilename($settings->number + 1) . ' -C ' . $this->getMainDirectoryName() .
            ' ' . $this->_getExcludedPaths() .
            ' -g ' . $this->_destination . DIRECTORY_SEPARATOR .
            $this->getSnapshotFileName() . ' ' . $this->getMainDirectoryBasename(),
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

    public function restore($time, $directory)
    {
        $settings = $this->getSettings();

        $restore_till_here = array_search($time, $settings->backups) + 1;

        for ($i = 1; $i <= $restore_till_here; $i++) {
            $exitCode = $this->_binary->run(
                ' xvf ' . $this->_destination . DIRECTORY_SEPARATOR .
                $this->getArchiveFilename($i) . ' -g ' . '/dev/null' .
                ' -C ' . $directory,
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
        file_put_contents($this->_destination . DIRECTORY_SEPARATOR . $this->getSettingsFile(), json_encode($settings));
    }

    /**
     * Get incremental snapshot filename.
     *
     * @return string
     */
    protected function getSnapshotFileName()
    {
        return $this->getMainDirectoryBasename() . '.snar';
    }

    /**
     * Get archive filename.
     *
     * @param int $archive_number
     * @return string
     */
    protected function getArchiveFilename($archive_number = 1)
    {
        return $this->getMainDirectoryBasename() . '.' . $archive_number . '.tar';
    }

    protected function getMainDirectoryBasename()
    {
        return basename($this->_main_directory);
    }

    protected function getMainDirectoryName()
    {
        return dirname($this->_main_directory, 1);
    }

    /**
     * @todo Fix file patterns to use relative path.
     *
     * @return string
     */
    private function _getExcludedPaths()
    {
        if (empty($this->_excluded_directories)) {
            return '';
        } else {
            return " --anchored --exclude=" . $this->getMainDirectoryBasename() . DIRECTORY_SEPARATOR .
            implode(
                " --exclude=" . $this->getMainDirectoryBasename() . DIRECTORY_SEPARATOR,
                $this->_excluded_directories
            ) . ' ';
        }
    }
}