<?php
/**
 * @author Ioannis Botis
 * @date 01/01/2017
 * @version: Bootstrap.php 7:43 pm
 * @since 01/01/2017
 */

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