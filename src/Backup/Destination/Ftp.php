<?php
/**
 * @author Ioannis Botis
 * @date 25/1/2017
 * @version: Ftp.php 7:34 pm
 * @since 25/1/2017
 */

namespace Backup\Destination;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp as Adapter;

class Ftp extends AbstractBase
{

    private $adapter;
    private $filesystem;

    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $this->adapter = new Adapter([
            'host' => 'localhost',
            'username' => 'user1',
            'password' => 'abc123',

            /** optional config settings */
            'port' => 21,
            'root' => '/',
            'passive' => true,
            //'ssl' => true,
            'timeout' => 30,
        ]);
        $this->filesystem = new Filesystem($this->adapter);
    }

    public function getType()
    {
        return self::FTP_TYPE;
    }

    public function getPath()
    {
        $settings = $this->getSettings();
        return $settings['path'];
    }

    public function isEmpty()
    {
        return true;
    }

    public function canAccess()
    {
        return ($this->filesystem->listContents('/') !== false);
    }

    public function read($file)
    {
        try {
            return $this->filesystem->read($file);
        } catch (\League\Flysystem\FileNotFoundException $e) {
            return false;
        }
    }

    public function write($filename, $contents)
    {
        try {
            return $this->filesystem->put($filename, $contents);
        } catch (\League\Flysystem\FileNotFoundException $e) {
            return false;
        }
    }

    public function listContents($dir = '', $recursive)
    {
        return $this->filesystem->listContents('/');
    }
}