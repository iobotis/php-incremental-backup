<?php
/**
 * @author Ioannis Botis
 * @date 24/1/2017
 * @version: Base.php 11:32 pm
 * @since 24/1/2017
 */

namespace Backup\Destination;

/**
 * Defines basic functions for various backup client storages.
 * eg. Local, ftp, dropbox.
 *
 * Interface Base
 * @package Backup\Destination
 */
interface Base
{
    const LOCAL_FOLDER_TYPE = 1;
    const SSH_TYPE = 2;
    const DROPBOX_TYPE = 3;

    public function __construct(array $settings);

    public function getType();

    public function getPath();

    public function getSettings();

    public function isEmpty();

    public function canAccess();
}