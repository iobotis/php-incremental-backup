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

1)
```php
$backup = new Duplicity( '/path/to/backup', '/path/to/save' );

if( $backup->verify()  != 0 ) {
    // back me up.
    echo 'Back up initiated' . "\n";
    $backup->execute();
}
else {
    echo 'No need to backup.' . "\n";
}
```
2)
```php
$backup = new Duplicity( '/path/to/backup', '/path/to/save' );

$backupClass = new IncrementalBackup ( $backup );

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
3)
```php
$backup = new Duplicity( '/path/to/backup', '/path/to/save' );

$backupClass = new IncrementalBackup ( $backup );

$backups = $backupClass->getAllBackups();

// Restore last backup to this directory.
$backupClass->restoreTo( end( $backups ), '/path/to/restore' );

```

4)
```php
use Backup\Tar;
use Backup\IncrementalBackup;

$backup = new Tar( '/path/to/backup', '/path/to/save' );

$backupClass = new IncrementalBackup ( $backup );

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

5)
```php
$backup = new Tar( '/path/to/backup', '/path/to/save' );

$backupClass = new IncrementalBackup ( $backup );

$backups = $backupClass->getAllBackups();

// Restore last backup to this directory.
$backupClass->restoreTo( end( $backups ), '/path/to/restore' );

```

How to run unit tests
---------------------
From the root folder run:
php {location of phpunit phar}/phpunit.phar
