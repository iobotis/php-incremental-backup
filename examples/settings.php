<?php
/**
 * @author Ioannis Botis
 * @date 27/12/2016
 * @version: settings.php 5:43 μμ
 * @since 27/12/2016
 */

$path_to_backup = '/path/to/backup';
$path_to_save = '/path/to/save';

$path_to_restore = '/path/to/restore';

/**
 * Simple autoloader.
 */
spl_autoload_register(function ($class) {

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/../src/';

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});