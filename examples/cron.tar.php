<?php
/**
 * @author Ioannis Botis
 * @date 5/1/2017
 * @version: cron.tar.php 10:45 pm
 * @since 5/1/2017
 */

require_once('settings.php');

use Backup\Tar;

$tar = new Tar($path_to_backup, $path_to_save);

echo "Version: " . $tar->getVersion() . "\n";

$tar->execute();