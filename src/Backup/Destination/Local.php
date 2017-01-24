<?php
/**
 * @author Ioannis Botis
 * @date 24/1/2017
 * @version: Local.php 11:47 pm
 * @since 24/1/2017
 */

namespace Backup\Destination;

use Backup\Destination\AbstractBase;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as LocalAdapter;

class Local extends AbstractBase
{
    private $path;
    protected $adapter;
    protected $filesystem;

    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $this->path = $settings['path'];
        $this->adapter = new LocalAdapter($settings['path']);
        $this->filesystem = new Filesystem($this->adapter);
    }

    public function getType()
    {
        return self::LOCAL_FOLDER_TYPE;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function isEmpty()
    {
        return empty($this->filesystem->listContents());
    }
}