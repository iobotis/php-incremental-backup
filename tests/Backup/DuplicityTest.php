<?php
/**
 * @author Ioannis Botis
 * @date 02/01/2017
 * @version: DuplicityTest.php 7:43 pm
 * @since 02/01/2017
 */

namespace Backup\tests;

use Backup\Binary;
use Backup\Tools\Command;
use Backup\Tools\Duplicity;
use Backup\Tools\TestHelper;

class DuplicityTest extends \PHPUnit_Framework_TestCase
{

    const PATH_TO_BACKUP = '/path/to/backup';
    const DESTINATION_PATH = '/path/destination';
    /**
     * @var Duplicity
     */
    protected $duplicity;

    /**
     * @var Binary
     */
    protected $binary;

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

        $this->binary
            ->expects($this->any())
            ->method('run')
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue(array('duplicity 0.7.06')));

        $this->assertTrue($this->duplicity->isInstalled());
    }

    /**
     *
     */
    public function testGetVersion()
    {
        $this->duplicity = $this->getDuplicityMock(null);
        $this->binary
            ->expects($this->once())
            ->method('run')
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(array('duplicity 0.7.06')));;

        // check that the last command was
        $this->assertEquals('0.7.06', $this->duplicity->getVersion());
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

        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with($this->stringStartsWith('--no-encryption verify --compare-data file://'), $this->equalTo(array()))
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(array('')));

        $this->assertEquals(Command::NO_CHANGES, $this->duplicity->verify());
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
        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with($this->stringStartsWith('--no-encryption ' . self::PATH_TO_BACKUP), $this->equalTo(array()))
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(array('')));

        $this->assertEquals(0, $this->duplicity->execute());
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
        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with($this->logicalNot($this->stringContains('--no-encryption')),
                $this->equalTo(array('PASSPHRASE' => 'abc')))
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(array('')));
        $this->duplicity->execute();

    }

    /**
     * @group 1
     * @dataProvider getCmdCollectionOutput
     * @param $cmd_output
     * @param $unix_timestamps
     */
    public function testGetAllBackups($cmd_output, $unix_timestamps)
    {
        $this->duplicity = $this->getDuplicityMock(array('getVersion'));
        $this->duplicity
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('0.6'));
        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with($this->stringStartsWith('--no-encryption collection-status'), $this->equalTo(array()))
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue($cmd_output));
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
        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with($this->stringContains('--exclude **dir1 --exclude **dir2'), $this->equalTo(array()))
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(''));
        $this->duplicity->setExludedSubDirectories(array('dir1', 'dir2'));

        $this->assertEquals(0, $this->duplicity->execute());
    }

    public function testRestore()
    {
        $unix_time = time() - 60 * 60 * 24 * 7;
        $d = new \DateTime();
        $d->setTimestamp($unix_time);
        $time = $d->format(\DateTime::W3C);

        $this->duplicity = $this->getDuplicityMock(array('getVersion', 'isDirEmpty'));
        $this->duplicity
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('0.6'));
        $this->duplicity
            ->expects($this->once())
            ->method('isDirEmpty')
            ->will($this->returnValue(true));
        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('--no-encryption restore'),
                    $this->stringContains('--time=' . $time)
                ),
                $this->equalTo(array())
            )
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(''));
        $this->duplicity->restore($unix_time, '/path/to/restore');
    }

    /**
     * @param $methods_to_mock
     * @return mixed
     */
    protected function getDuplicityMock($methods_to_mock)
    {
        $this->binary = $this->getMockBuilder(Binary::class)
            ->setMethods(array('run', 'getOutput'))
            ->setConstructorArgs(array('duplicity'))
            ->getMock();
        return $this->getMockBuilder(Duplicity::class)
            ->setMethods($methods_to_mock)
            ->setConstructorArgs(array(self::PATH_TO_BACKUP, self::DESTINATION_PATH, $this->binary))
            //->disableOriginalConstructor()
            ->getMock();
    }

    public function getCmdCollectionOutput()
    {
        $unix_timestamps = [time() - 60 * 60 * 24 * 5, time() - 60 * 60 * 24 * 4];
        return [
            [
                [
                    'Chain start time: Tue Jan 10 10:35:19 2017',
                    'Chain end time: Tue Jan 10 12:21:55 2017',
                    'Number of contained backup sets: 2',
                    'Total number of contained volumes: 2',
                    'Type of backup set:                            Time:      Num volumes:',
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