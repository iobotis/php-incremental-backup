<?php
/**
 * @author Ioannis Botis <ioannis.botis@interactivedata.com>
 * @date 4/1/2017
 * @version: Tar.php 3:52 μμ
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
     * Duplicity constructor.
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

    public function verify()
    {
        if (!is_readable($this->_destination)) {
            return false;
        }
        return true;

        // @todo check archives exist, main file.
        //self::exec(self::CMD . '');
    }

    public function execute()
    {
        // tar cvf archive.1.tar -g archive.snar backup
        self::exec(self::CMD . ' cvf ' . $this->_destination . DIRECTORY_SEPARATOR .
            $this->getArchiveFilename() . ' -g ' . $this->_destination . DIRECTORY_SEPARATOR .
            $this->getSnapshotFileName() . ' ' . $this->_main_directory , $output, $exitCode);
        $this->_output = $output;
        return $exitCode;
    }

    public function getAllBackups()
    {

    }

    public function restore($time, $directory)
    {
        
    }

    protected function getSnapshotFileName()
    {
        return basename($this->_main_directory) . '.snar';
    }

    protected function getArchiveFilename()
    {
        return basename($this->_main_directory) . '.1' . '.tar';
    }

    private static function exec($command, &$output, &$exitCode)
    {
        echo $command;
        exec($command, $output, $exitCode);
    }
}