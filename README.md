# php-incremental-backup

PHP incremental backup is a php library designed to support setting incremental backups run by cron scripts.
The library is a wrapper to different commands

Tools supported
---------------

* Duplicity
* Tar

These tools are used to perform incremental backups on the directories chosen.

Requirements:
-------------
* php 5.4 or greater installed.
* one of the above libraries to be installed in your system.

Examples:
---------

1) Simple Duplicity backup.
```php
use Backup\Tools\Factory as ToolFactory;

$settings = array(
    'path_to_backup' => $path_to_backup,
    'destination' => array(
        'type' => 'local',
        'path' => '/path/to/save'
    ),
//    'passphrase' => 'abcdef',
//    'exclude' => array('folder')
);

$backup = ToolFactory::create('Duplicity', $settings);
$backup->execute();

```
2) Simple Duplicity backup with wrapper class.
```php
use Backup\Tools\Factory as ToolFactory;
use Backup\IncrementalBackup;

use Backup\Tools\Factory as ToolFactory;

$settings = array(
    'path_to_backup' => $path_to_backup,
    'destination' => array(
        'type' => 'local',
        'path' => '/path/to/save'
    ),
//    'passphrase' => 'abcdef',
//    'exclude' => array('folder')
);

$backup = ToolFactory::create('Duplicity', $settings);
$backupClass = new IncrementalBackup ($backup);

$backups = $backupClass->getAllBackups();
foreach ($backups as $time) {
    echo 'There is a backup at ' . $time . "\n";
}

if( $backupClass->isChanged() ) {
    // back me up.
    echo 'Back up initiated' . "\n";
    $backupClass->createBackup();
}
else {
    echo 'No need to backup.' . "\n";
}
```
3) Simple Duplicity backup restore last backup.
```php
use Backup\Tools\Factory as ToolFactory;
use Backup\IncrementalBackup;

$settings = array(
    'path_to_backup' => $path_to_backup,
    'destination' => array(
        'type' => 'local',
        'path' => '/path/to/save'
    ),
//    'passphrase' => 'abcdef'
);

$duplicity = ToolFactory::create('Duplicity', $settings);
$backupClass = new IncrementalBackup ($duplicity);

// Restore last backup to this directory.
$backupClass->restoreTo(end( $backups ), '/path/to/restore');

```

4) Simple Tar backup.
```php
use Backup\Tools\Factory as ToolFactory;
use Backup\IncrementalBackup;

$settings = array(
    'path_to_backup' => $path_to_backup,
    'destination' => array(
        'type' => 'local',
        'path' => $path_to_save
    ),
    //'exclude' => array('exclude', 'exclude1')
);

$backup = ToolFactory::create('Tar', $settings);
$backupClass = new IncrementalBackup ($backup);

$backups = $backupClass->getAllBackups();
foreach ($backups as $time) {
    echo 'There is a backup at ' . $time . "\n";
}

if( $backupClass->isChanged() ) {
    // back me up.
    echo 'Back up initiated' . "\n";
    $backupClass->createBackup();
}
else {
    echo 'No need to backup.' . "\n";
}

```

5) Tar restore last backup.
```php
use Backup\Tools\Factory as ToolFactory;
use Backup\IncrementalBackup;

$settings = array(
    'path_to_backup' => $path_to_backup,
    'destination' => array(
        'type' => 'local',
        'path' => $path_to_save
    ),
);

$backup = ToolFactory::create('Tar', $settings);
$backupClass = new IncrementalBackup ( $backup );

$backups = $backupClass->getAllBackups();

// Restore last backup to this directory.
$backupClass->restoreTo( end( $backups ), '/path/to/restore' );

```

How to run unit tests
---------------------
From the root folder run:
php {location of phpunit phar}/phpunit.phar

or
Install composer dependencies and run the scripts defined in composer.json.
