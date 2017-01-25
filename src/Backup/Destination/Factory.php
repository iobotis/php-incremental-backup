<?php
/**
 * @author Ioannis Botis
 * @date 25/1/2017
 * @version: Factory.php 10:38 pm
 * @since 25/1/2017
 */

namespace Backup\Destination;


class Factory
{

    public static function create($settings)
    {
        if ($settings['type'] == 'local') {
            return new Local(array('path' => $settings['path']));
        } elseif ($settings['type'] == 'ftp') {
            return new Ftp($settings);
        }

        throw new \Backup\Exception\InvalidArgumentException('type not supported.');
    }
}