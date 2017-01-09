<?php
/**
 * @author Ioannis Botis
 * @date 1/9/17
 */

namespace Backup;

/**
 * Class Binary helps execute a binary setting environment variables and parameters to it.
 * eg. duplicity --no-encryption verify --compare-data file:///path/to/backup /destination 2>/dev/null
 * 
 * @package Backup
 */
class Binary
{

    /**
     * @var string
     */
    private $_path;

    /**
     * @var string
     */
    private $_command_suffix = '2>/dev/null';

    /**
     * @var string[]
     */
    private $_output;

    /**
     * Binary constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->_path = $path;
    }

    /**
     * Set the binary path eg. /usr/bin/duplicity.
     * s
     * @param string $path
     */
    public function setPath($path)
    {
        $this->_path = $path;
    }

    /**
     * Run binary with parameters.
     *
     * @param string $cmd_parameters
     * @param array $environment_vars
     */
    public function run($cmd_parameters, $environment_vars = array())
    {
        $vars = '';
        foreach ($environment_vars as $key => $value) {
            $vars .= $key . '=' . $value . " ";
        }
        self::exec($vars . $this->_path . ' ' . $cmd_parameters . ' ' . $this->_command_suffix, $output,
            $exitCode);
        $this->_output = $output;
        return $exitCode;
    }

    private function exec($command, &$output, &$exitCode)
    {
        exec($command, $output, $exitCode);
    }

    /**
     * @return \string[]
     */
    public function getOutput()
    {
        return$this->_output;
    }
}