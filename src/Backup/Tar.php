<?php
/**
 * @author Ioannis Botis
 * @date 4/1/2017
 * @version: Tar.php 10:52 pm
 * @since 4/1/2017
 */

namespace Backup;


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

    private $_output;

    private static $_version;

    /**
     * Tar constructor.
     *
     * @param string $directory the path to the directory to backup.
     * @param string $destination the path to the directory to keep the backup files.
     */
    public function __construct($directory, $destination)
    {
        $this->_setMainDirectory($directory);
        $this->_destination = $destination;
    }

    private function _setMainDirectory($directory)
    {
        if (!$this->isInstalled()) {
            throw new \Exception('Tar not installed');
        }
        if (!$this->directoryExists($directory)) {
            throw new \Exception('Tar path is invalid');
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
        self::exec(self::CMD . ' --version', $output, $exitCode);
        if ($exitCode) {
            return false;
        }
        return true;
    }

    /**
     * Returns the version of duplicity.
     * @return string
     */
    public function getVersion()
    {
        if (isset(self::$_version)) {
            return self::$_version;
        }
        self::exec(self::CMD . ' --version', $output, $exitCode);
        $output = explode(' ', $output[0]);
        return self::$_version = trim(str_replace('tar', '', end($output)));
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
        self::exec(self::CMD . ' --compare --file=' . $this->_destination . DIRECTORY_SEPARATOR .
            $this->getArchiveFilename($settings->number) . ' -C ' . $this->getMainDirectoryName() .
            ' ' . $this->getMainDirectoryBasename(), $output, $exitCode);
        if($exitCode == 0) {
            return self::NO_CHANGES;
        }
        elseif($exitCode == 1) {
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
        self::exec(self::CMD . ' cvf ' . $this->_destination . DIRECTORY_SEPARATOR .
            $this->getArchiveFilename($settings->number + 1) . ' -C ' . $this->getMainDirectoryName() .
            ' -g ' . $this->_destination . DIRECTORY_SEPARATOR .
            $this->getSnapshotFileName() . ' ' . $this->getMainDirectoryBasename(), $output, $exitCode);
        if ($exitCode == 0) {
            $this->saveSettings();
        }
        $this->_output = $output;
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

        $restore_till_here = array_search ($time, $settings->backups) + 1;

        for ($i = 1; $i <= $restore_till_here; $i++) {
            self::exec(self::CMD . ' xvf ' . $this->_destination . DIRECTORY_SEPARATOR .
                $this->getArchiveFilename($i) . ' -g ' . '/dev/null' .
                ' -C ' . $directory, $output, $exitCode);
        }
        $this->_output = $output;
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

    private static function exec($command, &$output, &$exitCode)
    {
        exec($command, $output, $exitCode);
    }
}