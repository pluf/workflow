<?php
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{

    /**
     * @beforeClass
     */
    public static function setPlfu ()
    {
        require_once 'Pluf.php';
    }

    /**
     * Can create new instance
     *
     * @test
     */
    public function instance ()
    {
        $wm = new Workflow_Machine();
        $this->assertTrue(isset($wm));
    }

    /**
     * Check class api
     *
     * @test
     */
    public function methods ()
    {
        $object = new Workflow_Machine();
        $method_names = array(
                'transact',
                
                'setStates',
                'setSignals',
                'setInitialState',
                'setProperty',
                
                'apply',
                'can'
        );
        foreach ($method_names as $method_name) {
            $this->assertTrue(method_exists($object, $method_name));
        }
    }
}