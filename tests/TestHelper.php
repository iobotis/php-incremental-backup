<?php
/**
 * 
 * User: 
 * Date: 1/4/17
 * Time: 12:01 AM
 */

namespace Backup;


class TestHelper
{

}

function exec($command, &$output, &$exitCode)
{
    echo 'worked';
    $output = array();
    $exitCode = 0;
    //exec($command, $output, $exitCode);
}