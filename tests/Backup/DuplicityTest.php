<?php
/**
 * @author Ioannis Botis
 * @date 02/01/2017
 * @version: DuplicityTest.php 7:43 pm
 * @since 02/01/2017
 */

namespace Backup\tests;

use Backup\Duplicity;
use Backup\TestHelper;

class DuplicityTest extends \PHPUnit_Framework_TestCase
{

    const PATH_TO_BACKUP = '/path/to/backup';
    const DESTINATION_PATH = '/path/destination';
    /**
     * @var Duplicity
     */
    protected $duplicity;

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        $this->duplicity = null;
        TestHelper::reset();
        parent::tearDown();
    }

    /**
     * Test that the correct command was called.
     */
    public function testIsInstalled()
    {
        $this->duplicity = $this->getDuplicityMock(null);
        $this->duplicity->isInstalled();

        $this->assertEquals(Duplicity::DUPLICITY_CMD . ' -V', end(TestHelper::$commands));
    }

    /**
     *
     */
    public function testGetVersion()
    {
        $this->duplicity = $this->getDuplicityMock(null);
        $this->duplicity->getVersion();

        // check that the last command was
        $this->assertEquals(Duplicity::DUPLICITY_CMD . ' -V', end(TestHelper::$commands));
    }

    /**
     * Test that the verify function is called correctly.
     */
    public function testVerify()
    {
        $this->duplicity = $this->getDuplicityMock(array('getVersion'));
        $this->duplicity
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('0.6'));
        $this->duplicity->verify();

        // check that the last command was
        $this->assertStringStartsWith(Duplicity::DUPLICITY_CMD . ' --no-encryption verify --compare-data file://',
            end(TestHelper::$commands));
    }

    /**
     * Test execute method is called correctly.
     */
    public function testExecute()
    {
        $this->duplicity = $this->getDuplicityMock(array('getVersion'));
        $this->duplicity
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('0.6'));
        $this->duplicity->execute();

        // check that the last command was
        $this->assertStringStartsWith(Duplicity::DUPLICITY_CMD . ' --no-encryption ' . self::PATH_TO_BACKUP,
            end(TestHelper::$commands));
    }

    /**
     * Test Passphrase is set as environment variable.
     *
     * @throws \Exception
     */
    public function testSetPassPhrase()
    {
        $this->duplicity = $this->getDuplicityMock(array('getVersion'));
        $this->duplicity
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('0.6'));
        $this->duplicity->setPassPhrase('abc');
        $this->duplicity->execute();

        // check that the last command contained the PASSPHRASE.
        $this->assertContains('PASSPHRASE=abc', end(TestHelper::$commands));
    }

    /**
     * @dataProvider getCmdCoolectionOutput
     * @param $cmd_output
     * @param $unix_timestamps
     */
    public function testGetAllBackups($cmd_output, $unix_timestamps)
    {
        TestHelper::$output = $cmd_output;
        $this->duplicity = $this->getDuplicityMock(array('getVersion'));
        $this->duplicity
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('0.6'));
        $backups = $this->duplicity->getAllBackups();

        // check that the backup timestamps were found from the command output.
        $this->assertEquals($backups, $unix_timestamps);
    }

    /**
     * Test exclude subdirectories function.
     */
    public function testSetExludedSubDirectories()
    {
        $this->duplicity = $this->getDuplicityMock(array('getVersion'));
        $this->duplicity
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('0.6'));
        $this->duplicity->setExludedSubDirectories(array('dir1', 'dir2'));
        $this->duplicity->execute();

        // check that the last command contained the excluded directories.
        $this->assertContains('--exclude **dir1 --exclude **dir2', end(TestHelper::$commands));
    }

    public function testRestore()
    {
        $time = time() - 60 * 60 * 24 * 7;
        $this->duplicity = $this->getDuplicityMock(array('getVersion', 'isDirEmpty'));
        $this->duplicity
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('0.6'));
        $this->duplicity
            ->expects($this->once())
            ->method('isDirEmpty')
            ->will($this->returnValue(true));
        $this->duplicity->restore($time, '/path/to/restore');

        $command = end(TestHelper::$commands);
        // check that the last command was
        $this->assertStringStartsWith(Duplicity::DUPLICITY_CMD . ' --no-encryption restore ', $command);
        $this->assertContains('/path/to/restore', $command);

        $d = new \DateTime();
        $d->setTimestamp($time);
        $time = $d->format(\DateTime::W3C);

        $this->assertContains('--time=' . $time, $command);
    }

    /**
     * @param $methods_to_mock
     * @return mixed
     */
    protected function getDuplicityMock($methods_to_mock)
    {
        return $this->getMockBuilder(Duplicity::class)
            ->setMethods($methods_to_mock)
            ->setConstructorArgs(array(self::PATH_TO_BACKUP, self::DESTINATION_PATH))
            //->disableOriginalConstructor()
            ->getMock();
    }

    public function getCmdCoolectionOutput()
    {
        $unix_timestamps = [time() - 60 * 60 * 24 * 5, time() - 60 * 60 * 24 * 4];
        return [
            [
                [
                    'Full         ' . date("D M j G:i:s Y", $unix_timestamps[0]) . '                 1',
                    'Incremental         ' . date("D M j G:i:s Y", $unix_timestamps[1]) . '                 1'
                ],
                [
                    $unix_timestamps[0],
                    $unix_timestamps[1]
                ]
            ]
        ];
    }
}