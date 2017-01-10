<?php
/**
 * @author Ioannis Botis
 * @date 9/1/2017
 * @version: CommandFactory.php 11:32 pm
 * @since 9/1/2017
 */

namespace Backup;

class CommandFactory
{

    private static $_classes_supported = ['Duplicity', 'Tar'];

    /**
     * Provide the classname without namespace to create an object that implements the Command interface.
     *
     * @param $class
     * @param array $settings
     * @throws \Exception
     */
    public static function create($class, array $settings)
    {
        // Check if class is supported.
        if (!in_array($class, self::$_classes_supported)) {
            throw new \Exception('Class not supported.');
        }

        if ($class === 'Duplicity') {
            $binary = new Binary('duplicity');

            if (empty($settings['path_to_backup']) || empty($settings['path_to_backup_at'])) {
                throw new \Exception('Please see the documentation for the settings needed.');
            }
            $duplicity = new Duplicity($settings['path_to_backup'], $settings['path_to_backup_at'], $binary);

            if (!empty($settings['passphrase']) && is_string($settings['passphrase'])) {
                $duplicity->setPassPhrase($settings['passphrase']);
            }

            return $duplicity;
        }
        elseif ($class === 'Tar') {
            $binary = new Binary('tar');

            if (empty($settings['path_to_backup']) || empty($settings['path_to_backup_at'])) {
                throw new \Exception('Please see the documentation for the settings needed.');
            }
            $tar = new Tar($settings['path_to_backup'], $settings['path_to_backup_at'], $binary);

            return $tar;
        }
        throw new \Exception('Class not yet implemented!');
    }
}