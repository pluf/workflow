<?php
use PHPUnit\Framework\TestCase;

class InvalidStateTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setPlfu(){
        require_once  'Pluf.php';
    }

    public function testTrueIsTrue ()
    {
        $foo = true;
        $this->assertTrue($foo);
    }
}