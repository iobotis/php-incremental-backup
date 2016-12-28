<?php
/**
 * @author Ioannis Botis
 * @date 28/12/2016
 * @version: Command.php 1:01 μμ
 * @since 28/12/2016
 */

namespace Backup;

/**
 * Interface Command
 * @package Backup
 */
interface Command
{
    /**
     * @return boolean
     */
    public function verify();

    public function execute();

    public function getAllBackups();
}