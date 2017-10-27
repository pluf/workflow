<?php
use PHPUnit\Framework\TestCase;
require_once 'Pluf.php';

class TurnstileSignalTest
{

    var $state;

    var $update_counter;

    function __construct()
    {
        $this->update_counter = 0;
    }

    public function update()
    {
        $this->update_counter ++;
    }
}

class SignalTest extends TestCase
{

    var $machine = null;

    public static $signalEvent = null;

    public static function signalPoint($event)
    {
        static::$signalEvent = $event;
    }

    /**
     * @beforeClass
     */
    public static function setPluf()
    {
        $GLOBALS['_PX_request'] = array();
    }

    /**
     * @before
     */
    public function instance()
    {
        // create maching
        $this->machine = new Workflow_Machine();
        $this->assertTrue(isset($this->machine));
        
        $initState = 'Locked';
        // Machine
        $states = array(
            Workflow_Machine::STATE_UNDEFINED => array(
                'next' => 'Locked'
            ),
            // State
            'Locked' => array(
                // Transaction or event
                'coin' => array(
                    'next' => 'Unlocked'
                ),
                'push' => array(
                    'next' => 'Locked'
                )
            ),
            'Unlocked' => array(
                // Transaction or event
                'coin' => array(
                    'next' => 'Unlocked'
                ),
                'push' => array(
                    'next' => 'Locked'
                )
            )
        );
        $this->machine->setStates($states)->setInitialState($initState)->setProperty('state');
    }

    /**
     * @test
     */
    public function sendState()
    {
        //
        $signal = 'Signal_' . rand();
        Pluf_Signal::connect($signal, array(
            'SignalTest',
            'signalPoint'
        ));
        $this->machine->setSignals(array(
            $signal
        ));
        
        // apply
        $object = new TurnstileSignalTest();
        $request = array();
        $this->machine->apply($object, 'push');
        $this->assertTrue($object->state === 'Locked');
        
        // check signal
        $this->assertNotNull(static::$signalEvent);
    }

    /**
     * @test
     */
    public function sendNullSignal()
    {
        $this->machine->setSignals(null);
        static::$signalEvent = null;
        // apply
        $object = new TurnstileSignalTest();
        $request = array();
        $this->machine->apply($object, 'push');
        $this->assertTrue($object->state === 'Locked');
        
        // check signal
        $this->assertNull(static::$signalEvent);
    }

    /**
     * @test
     */
    public function sendEmptySignal()
    {
        $signal = 'Signal_' . rand();
        Pluf_Signal::connect($signal, array(
            'SignalTest',
            'signalPoint'
        ));
        $this->machine->setSignals(array());
        
        // apply
        $object = new TurnstileSignalTest();
        $request = array();
        $this->machine->apply($object, 'push');
        $this->assertTrue($object->state === 'Locked');
        
        // check signal
        $this->assertNull(static::$signalEvent);
    }
}