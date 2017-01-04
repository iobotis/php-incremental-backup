<?php
/**
 * @author Ioannis Botis
 * @date 02/01/2017
 * @version: TestHelper.php 7:43 pm
 * @since 02/01/2017
 */

namespace Backup;

/**
 * Class TestHelper
 * @package Backup
 */
class TestHelper
{
    public static $commands = array();
    public static $output = array();

    public static function reset()
    {
        self::$commands = array();
    }
}

function exec($command, &$output, &$exitCode)
{
    TestHelper::$commands[] = $command;
    $output = TestHelper::$output;
    $exitCode = 0;
}

function is_dir($filename) {
    return true;
}