<?php
/**
 * @author Ioannis Botis
 * @date 9/1/2017
 * @version: CommandFactory.php 11:32 pm
 * @since 9/1/2017
 */

namespace Backup\Tools;

use Backup\Binary;
use Backup\FileSystem\Source;
use Backup\Destination\Local;

/**
 * Class CommandFactory
 * Factory design pattern for classes implementing the factoy pattern.
 *
 * @package Backup
 */
class Factory
{

    /**
     * Array of class names that the factory supports.
     *
     * @var string[]
     */
    private static $_classes_supported = ['Duplicity', 'Tar', 'Borg'];

    /**
     * Provide the classname without namespace and settings to create an object that implements the Command interface.
     * There are 2 ways to use this function:
     * supply all settings as 1 parameter eg.
     * create(array(
     *     'class' => 'Tar',
     *     'path_to_backup' => ...,
     *     ...
     * ))
     * or 2 parameters with the first being the class name and the 2nd the settings:
     * create('Tar', array('path_to_backup' => ..., ...))
     *
     * @param $class
     * @param array $settings
     * @throws \Backup\Exception\InvalidArgumentException
     */
    public static function create($class, array $settings = null)
    {
        // set class from settings if only 1 argument was used.
        if ($settings === null && is_array($class)) {
            $settings = $class;
            if (empty($settings['class'])) {
                throw new \Backup\Exception\InvalidArgumentException('Please specify the class.');
            }
            $class = $settings['class'];
        }
        // Check if class is supported.
        if (!in_array($class, self::$_classes_supported)) {
            throw new \Backup\Exception\InvalidArgumentException('Class not supported.');
        }

        if ($class === 'Duplicity') {
            $binary = new Binary('duplicity');

            if (empty($settings['path_to_backup']) || empty($settings['path_to_backup_at'])) {
                throw new \Backup\Exception\InvalidArgumentException('Please see the documentation for the settings needed.');
            }
            $duplicity = new Duplicity(
                new Source($settings['path_to_backup']),
                new Local(array('path' => $settings['path_to_backup_at'])),
                $binary
            );

            if (!empty($settings['passphrase']) && is_string($settings['passphrase'])) {
                $duplicity->setPassPhrase($settings['passphrase']);
            }

            if (!empty($settings['exclude']) && is_array($settings['exclude'])) {
                $duplicity->setExludedSubDirectories($settings['exclude']);
            }

            return $duplicity;
        } elseif ($class === 'Tar') {
            $binary = new Binary('tar');

            if (empty($settings['path_to_backup']) || empty($settings['path_to_backup_at'])) {
                throw new \Backup\Exception\InvalidArgumentException('Please see the documentation for the settings needed.');
            }
            $tar = new Tar(
                new Source($settings['path_to_backup']),
                new Local(array('path' => $settings['path_to_backup_at'])),
                $binary
            );

            if (!empty($settings['exclude']) && is_array($settings['exclude'])) {
                $tar->setExludedSubDirectories($settings['exclude']);
            }

            return $tar;
        } elseif ($class === 'Borg') {
            $binary = new Binary('borg');

            if (empty($settings['path_to_backup']) || empty($settings['path_to_backup_at'])) {
                throw new \Backup\Exception\InvalidArgumentException('Please see the documentation for the settings needed.');
            }
            $borg = new Borg(
                new Source($settings['path_to_backup']),
                new Local(array('path' => $settings['path_to_backup_at'])),
                $binary
            );

            if (!empty($settings['exclude']) && is_array($settings['exclude'])) {
                $borg->setExludedSubDirectories($settings['exclude']);
            }

            return $borg;
        }
        throw new \Backup\Exception\InvalidArgumentException('Class not yet implemented!');
    }
}