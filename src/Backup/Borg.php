<?php
/**
 * @author Ioannis Botis
 * @date 17/1/2017
 * @version: Borg.php 12:53 μμ
 * @since 17/1/2017
 */

namespace Backup;


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

    private static $_version;

    private $_output;

    /**
     * Borg constructor.
     * @param $directory
     * @param $destination
     * @param Binary $binary
     */
    public function __construct($directory, $destination, Binary $binary)
    {
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
        $this->_binary->run(' init ' . $encryption . $this->_destination, $this->getEnvironmentVars());
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
        if (!is_readable($this->_destination)) {
            return self::CORRUPT_DATA;
        }

        if($this->isDirEmpty($this->_destination)) {
            return self::NO_BACKUP_FOUND;
        }

        $exitCode = $this->_binary->run(
            'check ' . $this->_destination,
            $this->getEnvironmentVars()
        );

        if ($exitCode == 0) {
            return self::IS_CHANGED;
        }

        return self::CORRUPT_DATA;
    }

    public function execute()
    {
        if($this->isDirEmpty($this->_destination)) {
            $this->initializeRepo();
        }
        $exitCode = $this->_binary->run(
            'create ' .
            $this->_destination. '::' . time() . ' ' . $this->getMainDirectoryBasename(),
            $this->getEnvironmentVars(),
            $this->getMainDirectoryName()
        );
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

    public function restore($time, $directory)
    {
        $exitCode = $this->_binary->run(
            'extract ' .
            $this->_destination. '::' . $time,
            $this->getEnvironmentVars(),
            $directory
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

    protected function getMainDirectoryBasename()
    {
        return basename($this->_main_directory);
    }

    protected function getMainDirectoryName()
    {
        return dirname($this->_main_directory, 1);
    }
}