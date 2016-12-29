# php-duplicity

Duplicity is a great tool for incremental backups.

Here you can find a php class to use to backup your websites.

Requirements:
-------------

* duplicity to be installed in your system.

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