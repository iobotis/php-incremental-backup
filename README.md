# php-incremental-backup

PHP incremental backup is a php library designed to support setting incremental backups run by cron scripts.
The library is a wrapper to different commands

Tools supported
---------------

* [Duplicity](http://duplicity.nongnu.org/)
* [Tar](https://www.howtoforge.com/tutorial/linux-tar-command/)
* [Borg backup](https://borgbackup.readthedocs.io/en/stable/)

These tools are used to perform incremental backups on the directories chosen.

Requirements:
-------------
* php 5.4 or greater installed.
* one of the above libraries to be installed in your system.

How to install
--------------
1) Using composer

`composer require iobotis/php-incremental-backup`

2) Download and run `composer install`
Follow the examples in the examples folder

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
Advanced usage
--------------
1)Duplicity without Factory
```php
use Backup\Binary;
use Backup\FileSystem\Source;
use Backup\Destination\Factory as DesFactory;
use Backup\Tools\Duplicity;
use Backup\FileSystem\Folder;

$binary = new Binary('/usr/bin/duplicity');
$source = new Source('/var/www/example_com');
$destination = DesFactory::create('/var/backups/example_com');

$duplicity = new Duplicity($source,$destination,$binary);

$duplicity->setArchiveDir('/var/www/cache');
$duplicity->setExludedSubDirectories(array('cache', 'logs', 'tmp'));

// check if duplicity is installed.
$duplicity->isInstalled();

// get duplicity version.
$duplicity->getVersion();

// verify backup location.
$duplicity->verify();

// backup if needed.
$duplicity->execute();

// retrieve existing backups.
$backups = $duplicity->getAllBackups();

// restore 1st backup.
$folder = new Folder('/var/www/example_com');
$duplicity->restore($backups[0], $folder);

```

How to run unit tests
---------------------
From the root folder run:
php {location of phpunit phar}/phpunit.phar

or
Install composer dependencies and run the scripts defined in composer.json.
