<?php
namespace Pluf\Test\Workflow;

use Pluf\Test\TestCase;
use Pluf\Workflow\Event;
use Pluf\Workflow\Machine;
use Pluf;

class ApiTest extends TestCase
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

    /**
     * Can create new instance
     *
     * @test
     */
    public function instance()
    {
        // Machine
        $wm = new Machine();
        $this->assertTrue(isset($wm));
        // Event
        $request = null;
        $object = null;
        $action = null;
        $state = null;
        $transaction = null;
        $event = new Event($request, $object, $action, $state, $transaction);
        $this->assertTrue(isset($event));
    }

    /**
     * Check class api
     *
     * @test
     */
    public function methods()
    {
        $object = new Machine();
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