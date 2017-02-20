<?php
/**
 * @author Ioannis Botis
 * @date 24/1/2017
 * @version: Local.php 11:47 pm
 * @since 24/1/2017
 */

namespace Backup\Destination;

use Backup\Destination\AbstractBase;

use Backup\FileSystem\Folder;
use League\Flysystem\Adapter\Local as FlyLocal;
use League\Flysystem\Filesystem;

class Local extends AbstractBase
{
    protected $folder;

    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $this->folder = new Folder($settings['path']);
    }

    public function getType()
    {
        return self::LOCAL_FOLDER_TYPE;
    }

    public function getPath()
    {
        return $this->folder->getPath();
    }

    public function isEmpty()
    {
        return $this->folder->isEmpty();
    }

    public function canAccess()
    {
        return $this->folder->isReadable();
    }

    public function read($file)
    {
        $full_file = $this->getPath() . $file;
        if (file_exists($full_file)) {
            return file_get_contents($full_file);
        }
        return null;
    }

    public function listContents($dir = '', $recursive = false) {
        return null;
    }

    public function write($filename, $contents)
    {
        $settings = $this->getSettings();
        $adapter = new FlyLocal($settings['path']);
        $filesystem = new Filesystem($adapter);
        return $filesystem->write($filename, $contents);
    }

    public function __toString()
    {
        return $this->folder->__toString();
    }
}