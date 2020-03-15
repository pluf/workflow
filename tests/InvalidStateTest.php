<?php
namespace Pluf\Test\Workflow;

use Pluf\Test\TestCase;
use Pluf;

class InvalidStateTest extends TestCase
{

    /**
     *
     * @beforeClass
     */
    public static function setPlfu()
    {
        Pluf::start('conf/config.php');
        $GLOBALS['_PX_request'] = array();
    }

    public function testTrueIsTrue()
    {
        $foo = true;
        $this->assertTrue($foo);
    }
}