<?php
// namespace Pluf\Test\Workflow;

// use Pluf\NoteBook\Book;
// use Pluf\Test\TestCase;
// use Pluf\Workflow\Machine;
// use Pluf;
// use Pluf_Migration;
// use Pluf_Signal;
// use Pluf\Workflow\Event;

// class SignalTest extends TestCase
// {

//     var $machine = null;

//     public static int $counter = 0;

//     public static ?string $signalEvent = null;

//     public static function signalPoint($event)
//     {
//         self::$signalEvent = $event;
//         self::$counter ++;
//     }

//     /**
//      *
//      * @beforeClass
//      */
//     public static function setupApplication()
//     {
//         Pluf::start(__DIR__ . '/conf/config.php');
//         $m = new Pluf_Migration();
//         $m->install();
//     }

//     /**
//      *
//      * @afterClass
//      */
//     public static function deleteApplication()
//     {
//         $m = new Pluf_Migration();
//         $m->uninstall();
//     }

//     public function cleanTestVariables(): void
//     {
//         self::$counter = 0;
//         self::$signalEvent = null;
//     }

//     /**
//      *
//      * @before
//      */
//     public function instance()
//     {
//         // create maching
//         $this->machine = new Machine();
//         $this->assertTrue(isset($this->machine));

//         $initState = 'Locked';
//         // Machine
//         $states = array(
//             Machine::STATE_UNDEFINED => array(
//                 'next' => 'Locked'
//             ),
//             // State
//             'Locked' => array(
//                 // Transaction or event
//                 'coin' => array(
//                     'next' => 'Unlocked'
//                 ),
//                 'push' => array(
//                     'next' => 'Locked'
//                 )
//             ),
//             'Unlocked' => array(
//                 // Transaction or event
//                 'coin' => array(
//                     'next' => 'Unlocked'
//                 ),
//                 'push' => array(
//                     'next' => 'Locked'
//                 )
//             )
//         );
//         $this->machine->setStates($states)
//             ->setInitialState($initState)
//             ->setProperty('state');
//     }

//     /**
//      *
//      * @test
//      */
//     public function sendState()
//     {
//         //
//         $signal = 'Signal_' . rand();
//         Pluf_Signal::connect($signal, array(
//             '\Pluf\Test\Workflow\SignalTest',
//             'signalPoint'
//         ));
//         $this->machine->setSignals(array(
//             $signal
//         ));

//         // apply
//         $object = new Book();
//         $this->machine->apply($object, 'push');
//         $this->assertTrue($object->state === 'Locked');

//         // check signal
//         $this->assertNotNull(static::$signalEvent);
//     }

//     /**
//      *
//      * @test
//      */
//     public function sendNullSignal()
//     {
//         $this->machine->setSignals(null);
//         static::$signalEvent = null;
//         // apply
//         $object = new Book();
//         $this->machine->apply($object, 'push');
//         $this->assertTrue($object->state === 'Locked');

//         // check signal
//         $this->assertNull(static::$signalEvent);
//     }

//     /**
//      *
//      * @test
//      */
//     public function sendEmptySignal()
//     {
//         $signal = 'Signal_' . rand();
//         Pluf_Signal::connect($signal, array(
//             '\Pluf\Test\Workflow\SignalTest',
//             'signalPoint'
//         ));
//         $this->machine->setSignals(array());

//         // apply
//         $object = new Book();
//         $this->machine->apply($object, 'push');
//         $this->assertTrue($object->state === 'Locked');

//         // check signal
//         $this->assertNull(static::$signalEvent);
//     }
// }