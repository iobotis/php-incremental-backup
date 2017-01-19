<?php
/**
 * @author Ioannis Botis
 * @date 19/1/2017
 * @version: Folder.php 8:52 pm
 * @since 19/1/2017
 */

namespace Backup\FileSystem;


class Folder
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function exists()
    {
        return is_dir($this->path);
    }

    public function isEmpty()
    {
        if (!is_readable($this->path)) {
            return null;
        }
        $handle = opendir($this->path);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                return false;
            }
        }
        return true;
    }

    public function isReadable()
    {
        return is_readable($this->path);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getBasename()
    {
        return basename($this->path);
    }

    public function __toString()
    {
        return $this->path;
    }
}