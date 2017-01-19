<?php
/**
 * @author Ioannis Botis
 * @date 1/9/17
 */

namespace Backup;

/**
 * Class Binary helps execute a binary setting environment variables and parameters to it.
 * eg. duplicity --no-encryption verify --compare-data file:///path/to/backup /destination 2>/dev/null
 * This is used as an abstraction layer to execute a binary with parameters and environment variables.
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
    private $_command_suffix = '2>&1';

    /**
     * @var string[]
     */
    private $_output;

    /**
     * @var string[]
     */
    private $_execution_list = array();

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

    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Run binary with parameters.
     *
     * @param string $cmd_parameters
     * @param array $environment_vars
     */
    public function run($cmd_parameters, $environment_vars = array(), $directory = null)
    {
        $vars = '';
        foreach ($environment_vars as $key => $value) {
            $vars .= $key . '=' . $value . " ";
        }
        $command = $vars . $this->_path . ' ' . $cmd_parameters . ' ' . $this->_command_suffix;
        if($directory) {
            $command = '(cd ' . $directory . ' && ' . $command . ")";
        }
        self::exec($command, $output,
            $exitCode);
        $this->_output = $output;
        return $exitCode;
    }

    private function exec($command, &$output, &$exitCode)
    {
        exec($command, $output, $exitCode);
        $this->_execution_list[] = $command;
    }

    /**
     * @return \string[]
     */
    public function getOutput()
    {
        return $this->_output;
    }

    public function getExecutionList()
    {
        return $this->_execution_list;
    }
}