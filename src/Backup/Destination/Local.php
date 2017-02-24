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
    private $adapter;
    private $filesystem;

    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $this->adapter = new FlyLocal($settings['path']);
        $this->filesystem = new Filesystem($this->adapter);
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
        return $this->filesystem->read($file);
    }

    public function listContents($dir = '', $recursive = false)
    {
        return $this->filesystem->listContents($dir, $recursive);
    }

    public function write($filename, $contents)
    {
        if ($contents === null) {
            return $this->filesystem->createDir($filename);
        }
        return $this->filesystem->put($filename, $contents);
    }
}