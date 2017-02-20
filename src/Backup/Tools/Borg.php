<?php
/**
 * @author Ioannis Botis
 * @date 17/1/2017
 * @version: Borg.php 12:53 μμ
 * @since 17/1/2017
 */

namespace Backup\Tools;

use Backup\Binary;
use Backup\FileSystem\TmpFileService;

class Borg implements Command
{

    /**
     * @var array options of duplicity command.
     */
    private $_options = array(
        '--encryption=none' => array(
            'since' => '0.1',
            'use' => false,
        ),
        '--dry-run' => array(
            'since' => '0.1',
            'use' => false,
        ),
    );

    /**
     * @var string optional passphrase specified.
     */
    private $_passphrase;
    /**
     * @var \Backup\FileSystem\Source the main directory to backup.
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

    private static $_version;

    private $_output;

    /**
     * Borg constructor.
     * @param $directory
     * @param $destination
     * @param Binary $binary
     */
    public function __construct(
        \Backup\FileSystem\Source $directory,
        \Backup\Destination\Base $destination,
        Binary $binary
    ) {
        $this->_binary = $binary;
        $this->_main_directory = $directory;
        $this->_destination = $destination;
    }

    /**
     * Initialize the repo.
     */
    protected function initializeRepo()
    {
        $encryption = '';
        if (!isset($this->_passphrase)) {
            $encryption = '--encryption=none ';
        }
        if ($this->_destination->getType() === \Backup\Destination\Base::LOCAL_FOLDER_TYPE) {
            $path = $this->_destination->getPath();
        } elseif ($this->_destination->getType() === \Backup\Destination\Base::FTP_TYPE) {

        }
        $this->_binary->run(' init ' . $encryption . $path, $this->getEnvironmentVars());
    }

    private function _cloneToLocal() {
        $tmp = new TmpFileService('/tmp');
        //$snapshot_file = $tmp->create($snapshot_file_data);
        $contents = $this->_destination->listContents();
        $temporary_directory = $tmp->mkdir();
        foreach ($contents as $content) {
            $data = $this->_destination->read($content['basename']);

        }
        return $temporary_directory;
    }
    /**
     * Set a passphrase to encrypt or decrypt the backup.
     *
     * @param $passphrase
     */
    public function setPassPhrase($passphrase)
    {
        if (!is_string($passphrase)) {
            throw new \Backup\Exception\InvalidArgumentException('Passphrase should be a string');
        }
        $this->_passphrase = $passphrase;
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
     * Returns the version of duplicity.
     * @return string
     */
    public function getVersion()
    {
        if (isset(self::$_version)) {
            return self::$_version;
        }
        $this->_binary->run(' -V');
        $output = implode('', $this->_binary->getOutput());
        return self::$_version = trim(str_replace('borg', '', $output));
    }

    public function verify()
    {
        if (!$this->_destination->canAccess()) {
            return self::CORRUPT_DATA;
        }

        if ($this->_destination->isEmpty()) {
            return self::NO_BACKUP_FOUND;
        }

        $exitCode = $this->_binary->run(
            'check ' . $this->_destination->getPath(),
            $this->getEnvironmentVars()
        );

        if ($exitCode == 0) {
            return self::IS_CHANGED;
        }

        $this->_output = $this->_binary->getOutput();
        return self::CORRUPT_DATA;
    }

    public function execute()
    {
        if ($this->_destination->isEmpty()) {
            $this->initializeRepo();
        }
        $exitCode = $this->_binary->run(
            'create ' . $this->_getExcludedPaths() .
            $this->_destination . '::' . time() . ' .' . DIRECTORY_SEPARATOR,
            $this->getEnvironmentVars(),
            $this->_main_directory->getPath()
        );
        if ($exitCode) {
            throw new \Backup\Exception\InvalidArgumentException('Backup destination should be empty or a valid borg repo');
        }
        $this->_output = $this->_binary->getOutput();
        return $exitCode;
    }

    public function getAllBackups()
    {
        $exitCode = $this->_binary->run(
            'list ' .
            $this->_destination,
            $this->getEnvironmentVars()
        );
        if ($exitCode != 0) {
            return array();
        }
        $this->_output = $this->_binary->getOutput();

        $backups = array();
        foreach ($this->_output as $line) {
            if (preg_match("/([0-9]+)[\s]+(.*)/", $line, $results)) {
                $backups[] = trim($results[1]);
            }
        }
        return $backups;
    }

    public function restore($time, \Backup\FileSystem\Folder $directory)
    {
        $exitCode = $this->_binary->run(
            'extract ' .
            $this->_destination . '::' . $time,
            $this->getEnvironmentVars(),
            $directory->getPath()
        );
        $this->_output = $this->_binary->getOutput();
        return $exitCode;
    }

    private function _getOptions()
    {
        $options = array();

        return implode(' ', $options);
    }

    private function _isSupported($since)
    {
        $version = $this->getVersion();
        return version_compare($version, $since, '>=');
    }

    protected function isDirEmpty($dir)
    {
        if (!is_readable($dir)) {
            return null;
        }
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                return false;
            }
        }
        return true;
    }

    protected function getEnvironmentVars()
    {
        $vars = array();
        if (isset($this->_passphrase)) {
            $vars['BORG_PASSPHRASE'] = $this->_passphrase;
        }
        return $vars;
    }

    private function _getExcludedPaths()
    {
        if (empty($this->_excluded_directories)) {
            return '';
        } else {
            return " -e '." . DIRECTORY_SEPARATOR .
            implode(
                "' -e '." . DIRECTORY_SEPARATOR,
                $this->_excluded_directories
            ) . "' ";
        }
    }

    public function getOutput()
    {
        return $this->_binary->getOutput();
    }
}