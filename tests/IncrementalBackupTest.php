<?php
/**
 * @author Ioannis Botis
 * @date 01/01/2017
 * @version: IncrementalBackupTest.php 7:43 pm
 * @since 01/01/2017
 */

namespace Backup\Tests;

use Backup\IncrementalBackup;
use Backup\Command;

class IncrementalBackupTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Command
     */
    protected $command;
    /**
     * @var IncrementalBackup
     */
    protected $backup;

    /**
     * Set up.
     */
    public function setUp()
    {
        $this->command = $this->getMockBuilder('Backup\Command')
            ->setMethods(array('verify', 'execute', 'getAllBackups', 'restore'))
            ->getMockForAbstractClass();
        $this->backup = new IncrementalBackup($this->command);
        parent::setUp();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test whether isChanged returns true when verify returns 1.
     *
     * @group base
     */
    public function testIsChanged()
    {
        $this->command->expects($this->once())
            ->method('verify')
            ->will($this->returnValue(1));

        $this->assertTrue($this->backup->isChanged());
    }

    /**
     * Test whether isChanged returns false when verify returns 0.
     *
     * @group base
     */
    public function testIsNotChanged()
    {
        $this->command->expects($this->once())
            ->method('verify')
            ->will($this->returnValue(0));

        $this->assertFalse($this->backup->isChanged());
    }

    /**
     * Test whether execute is called and createBackup returns the same value.
     *
     * @group base
     */
    public function testCreateBackup()
    {
        $this->command->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(0));
        $this->assertEquals(0, $this->backup->createBackup());
    }

    /**
     * Test whether getAllBackups is called.
     *
     * @group base
     */
    public function testGetAllBackups()
    {
        $backups = [111111, 123478];
        $this->command->expects($this->once())
            ->method('getAllBackups')
            ->will($this->returnValue($backups));
        $this->assertEquals($backups, $this->backup->getAllBackups());
    }

    public function testRestoreTo()
    {
        $this->command->expects( $this->once())
            ->method('restore')
            ->with(1111,'/restore/dir')
            ->will($this->returnValue(0));

        $this->assertTrue($this->backup->restoreTo(1111,'/restore/dir'));
    }
}