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
use Backup\Destination\Local;

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
     * @var \Backup\Destination\Local
     */
    private $_local_destination;

    /**
     * @var bool whether a new backup was performed.
     */
    private $_new_backup_taken = false;

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
        $path = $this->getDestination()->getPath();
        $exitCode = $this->_binary->run(' init ' . $encryption . $path, $this->getEnvironmentVars());
        if ($exitCode) {
            throw new \Backup\Exception\InvalidArgumentException('Backup initialization failed');
        }
    }

    /**
     * Get the destination to Backup at with borg.
     * If destination is not local, create a tmp local directory and sync contents.
     *
     * @return \Backup\Destination\Base|Local
     */
    protected function getDestination()
    {
        if ($this->_destination->getType() === \Backup\Destination\Base::LOCAL_FOLDER_TYPE) {
            return $this->_destination;
        } elseif ($this->_destination->getType() === \Backup\Destination\Base::FTP_TYPE) {
            if (!isset($this->_local_destination)) {
                $this->_local_destination = $this->_cloneToLocal();
            }
            return $this->_local_destination;
        }
    }

    private function _cloneToLocal()
    {
        $tmp = new TmpFileService('/tmp');
        $temporary_directory = $tmp->mkdir();
        $folder = new Local(array('path' => $temporary_directory));
        $this->_synchronize($this->_destination, $folder);
        return $folder;
    }

    private function _synchronize($source, $destination)
    {
        $contents = $source->listContents('', true);
        foreach ($contents as $content) {
            if ($content['type'] === 'dir') {
                $data = null;
            } elseif ($content['type'] === 'file') {
                $data = $source->read($content['path']);
            }

            $destination->write($content['path'], $data);
        }
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
            'check ' . $this->getDestination()->getPath(),
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
        if ($this->getDestination()->isEmpty()) {
            $this->initializeRepo();
        }
        $exitCode = $this->_binary->run(
            'create ' . $this->_getExcludedPaths() .
            $this->getDestination()->getPath() . '::' . time() . ' .' . DIRECTORY_SEPARATOR,
            $this->getEnvironmentVars() + array('BORG_RELOCATED_REPO_ACCESS_IS_OK' => 'yes'),
            $this->_main_directory->getPath()
        );
        if ($exitCode) {
            throw new \Backup\Exception\InvalidArgumentException('Backup destination should be empty or a valid borg repo');
        }
        $this->_new_backup_taken = true;
        $this->_output = $this->_binary->getOutput();
        return $exitCode;
    }

    public function getAllBackups()
    {
        $exitCode = $this->_binary->run(
            'list ' .
            $this->getDestination()->getPath(),
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
            $this->getDestination()->getPath() . '::' . $time,
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

    /**
     * When the object is destroyed update the destination if needed.
     */
    public function __destruct()
    {
        if(isset($this->_local_destination) && $this->_new_backup_taken) {
            $this->_synchronize($this->_local_destination, $this->_destination);
        }
    }
}