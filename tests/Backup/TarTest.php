<?php
/**
 * @author Ioannis Botis
 * @date 19/1/2017
 * @version: TarTest.php 7:56 μμ
 * @since 19/1/2017
 */

namespace Backup\tests;

use Backup\Binary;
use Backup\FileSystem\Folder;
use Backup\FileSystem\Source;
use Backup\FileSystem\Destination;
use Backup\Tools\Command;
use Backup\Tools\Tar;

class TarTest extends \PHPUnit_Framework_TestCase
{
    const PATH_TO_BACKUP = '/path/to/backup';
    const DESTINATION_PATH = '/path/destination';
    /**
     * @var Duplicity
     */
    protected $tar;

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
        $this->tar = null;
        parent::tearDown();
    }

    /**
     * Test that the correct command was called.
     */
    public function testIsInstalled()
    {
        $this->tar = $this->getTarMock(null);

        $this->binary
            ->expects($this->any())
            ->method('run')
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue(array('tar 1.2')));

        $this->assertTrue($this->tar->isInstalled());
    }

    public function testGetVersion()
    {
        $this->tar = $this->getTarMock(null);
        $this->binary
            ->expects($this->once())
            ->method('run')
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(array('tar 1.2')));;

        // check that the last command was
        $this->assertEquals('1.2', $this->tar->getVersion());
    }

    /**
     * Test that the verify function is called correctly.
     */
    public function testVerify()
    {
        $this->tar = $this->getTarMock(array('getVersion', 'getSettings'));
        $this->tar
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.2'));

        $this->tar
            ->expects($this->any())
            ->method('getSettings')
            ->will($this->returnValue((object)array("number" => 1)));

        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with($this->stringStartsWith(' --compare --file='), $this->equalTo(array()))
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue(array('')));

        $this->assertEquals(Command::NO_CHANGES, $this->tar->verify());
    }

    /**
     * Test execute method is called correctly.
     */
    public function testExecute()
    {
        $this->tar = $this->getTarMock(array('getVersion', 'getSettings', 'saveSettings'));
        $this->tar
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.2'));

        $this->tar
            ->expects($this->any())
            ->method('getSettings')
            ->will($this->returnValue((object)array("number" => 1)));
        $this->tar
            ->expects($this->any())
            ->method('saveSettings')
            ->will($this->returnValue(true));
        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with($this->stringStartsWith(' cvf'), $this->equalTo(array()))
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(array('')));

        $this->assertEquals(0, $this->tar->execute());
    }

    /**
     * Test Passphrase is set as environment variable.
     *
     * @throws \Exception
     */
    public function testSetPassPhrase()
    {
        $this->markTestSkipped("Passphrase not yet implemented!");
        $this->tar = $this->getTarMock(array('getVersion', 'getSettings', 'saveSettings'));
        $this->tar
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.2'));

        $this->tar
            ->expects($this->any())
            ->method('getSettings')
            ->will($this->returnValue((object)array("number" => 1)));
        $this->tar
            ->expects($this->any())
            ->method('saveSettings')
            ->will($this->returnValue(true));
        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with($this->stringStartsWith(' cvf'), $this->equalTo(array()))
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(array('')));

        $this->tar->setPassPhrase('abc');
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
        $this->tar->execute();

    }

    /**
     *
     */
    public function testGetAllBackups()
    {
        $expected = array(1,2,3,4);
        $this->tar = $this->getTarMock(array('getVersion', 'getSettings', 'saveSettings'));
        $this->tar
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.2'));

        $this->tar
            ->expects($this->any())
            ->method('getSettings')
            ->will($this->returnValue((object)array("number" => 1, "backups" => $expected)));
        $this->tar
            ->expects($this->any())
            ->method('saveSettings')
            ->will($this->returnValue(true));

        $backups = $this->tar->getAllBackups();

        // check that the backup timestamps were found from the command output.
        $this->assertEquals($backups, $expected);
    }

    /**
     * Test exclude subdirectories function.
     */
    public function testSetExludedSubDirectories()
    {
        $expected = array(1,2,3,4);
        $this->tar = $this->getTarMock(array('getVersion', 'getSettings', 'saveSettings'));
        $this->tar
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.2'));

        $this->tar
            ->expects($this->any())
            ->method('getSettings')
            ->will($this->returnValue((object)array("number" => 1, "backups" => $expected)));
        $this->tar
            ->expects($this->any())
            ->method('saveSettings')
            ->will($this->returnValue(true));

        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with($this->stringContains('--exclude=./dir1 --exclude=./dir2'), $this->equalTo(array()))
            ->will($this->returnValue(0));

        $this->tar->setExludedSubDirectories(array('dir1', 'dir2'));

        $this->assertEquals(0, $this->tar->execute());
    }

    public function testRestore()
    {
        $unix_time = time() - 60 * 60 * 24 * 7;
        $d = new \DateTime();
        $d->setTimestamp($unix_time);
        $time = $d->format(\DateTime::W3C);

        $expected = array(1,2,3,4);
        $this->tar = $this->getTarMock(array('getVersion', 'getSettings', 'saveSettings'));
        $this->tar
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.2'));

        $this->tar
            ->expects($this->any())
            ->method('getSettings')
            ->will($this->returnValue((object)array("number" => 1, "backups" => $expected)));

        $this->binary
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('xvf '),
                    $this->stringContains('-g /dev/null')
                ),
                $this->equalTo(array())
            )
            ->will($this->returnValue(0));
        $this->binary
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(''));
        $folderMock = $this->getMockBuilder(Folder::class)
            ->setMethods(array('exists', 'isEmpty'))
            ->setConstructorArgs(array('/path/to/restore'))
            ->getMock();
        $folderMock->expects($this->any())
            ->method('exists')
            ->will($this->returnValue(true));
        $folderMock->expects($this->any())
            ->method('isEmpty')
            ->will($this->returnValue(true));

        $this->tar->restore($unix_time, $folderMock);
    }

    /**
     * @param $methods_to_mock
     * @return mixed
     */
    protected function getTarMock($methods_to_mock)
    {
        $sourceMock = $this->getMockBuilder(Source::class)
            ->setMethods(array('exists'))
            ->setConstructorArgs(array(self::PATH_TO_BACKUP))
            ->getMock();
        $sourceMock->expects($this->any())
            ->method('exists')
            ->will($this->returnValue(true));
        $destinationMock = $this->getMockBuilder(Destination::class)
            ->setMethods(array('exists', 'isReadable'))
            ->setConstructorArgs(array(self::DESTINATION_PATH))
            ->getMock();
        $destinationMock->expects($this->any())
            ->method('exists')
            ->will($this->returnValue(true));
        $destinationMock->expects($this->any())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $this->binary = $this->getMockBuilder(Binary::class)
            ->setMethods(array('run', 'getOutput'))
            ->setConstructorArgs(array('duplicity'))
            ->getMock();
        return $this->getMockBuilder(Tar::class)
            ->setMethods($methods_to_mock)
            ->setConstructorArgs(array($sourceMock, $destinationMock, $this->binary))
            //->disableOriginalConstructor()
            ->getMock();
    }
}