<?php
/**
 * @author Ioannis Botis
 * @date 3/2/2017
 * @version: TmpFileService.php 7:53 pm
 * @since 3/2/2017
 */

namespace Backup\FileSystem;


class TmpFileService
{

    private $_path;

    public function __construct($path)
    {
        $this->_path = $path;
    }

    public function create($contents)
    {
        $tmpfname = tempnam($this->_path, "Tmp");
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $contents);
        fclose($handle);

        return $tmpfname;
    }

    function mkdir()
    {
        $tempfile = tempnam($this->_path, 'Tmp');
        if (file_exists($tempfile)) {
            unlink($tempfile);
        }
        mkdir($tempfile);
        if (is_dir($tempfile)) {
            return $tempfile;
        }
        else {
            return null;
        }
    }

}