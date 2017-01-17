<?php
/**
 * @author Ioannis Botis
 * @date 02/01/2017
 * @version: TestHelper.php 7:43 pm
 * @since 02/01/2017
 */

namespace Backup\Tools;

/**
 * Class TestHelper
 * @package Backup
 */
class TestHelper
{
    public static $output = array();

    public static function reset()
    {
    }
}

function is_dir($filename)
{
    return true;
}