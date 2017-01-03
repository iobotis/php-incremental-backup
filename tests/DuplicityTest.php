<?php
/**
 * Created by PhpStorm.
 * User: jb
 * Date: 1/2/17
 * Time: 9:58 PM
 */

namespace Backup\tests;

use Backup\Duplicity;

class DuplicityTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Duplicity
     */
    protected $duplicity;

    public function setUp()
    {
        Duplicity::$unitTestEnabled = true;
        $this->duplicity = new Duplicity(__DIR__, __DIR__);
        parent::setUp();
    }

    public function tearDown()
    {
        $this->duplicity = null;
        parent::tearDown();
    }

    public function testIsInstalled()
    {
        $this->duplicity->isInstalled();
    }
}