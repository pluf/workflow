<?php
namespace Pluf\Test\Workflow;

use Pluf\Test\TestCase;
use Pluf\Workflow\Machine;
use Pluf;
use Pluf_Migration;
use Pluf\NoteBook\Book;

/**
 * Trunstile test
 *
 * An example of a mechanism that can be modeled by a state machine is a
 * turnstile. A turnstile, used to control access to subways and amusement
 * park rides, is a gate with three rotating arms at waist height, one across
 * the entryway.
 * Initially the arms are locked, blocking the entry, preventing patrons from
 * passing through. Depositing a coin or token in a slot on the turnstile
 * unlocks the arms, allowing a single customer to push through. After the
 * customer passes through, the arms are locked again until another coin is
 * inserted.
 *
 * Considered as a state machine, the turnstile has two possible states: Locked
 * and Unlocked. There are two possible inputs that affect its state: putting
 * a coin in the slot (coin) and pushing the arm (push). In the locked state,
 * pushing on the arm has no effect; no matter how many times the input push is
 * given, it stays in the locked state. Putting a coin in – that is, giving the
 * machine a coin input – shifts the state from Locked to Unlocked. In the
 * unlocked state, putting additional coins in has no effect; that is, giving
 * additional coin inputs does not change the state. However, a customer pushing
 * through the arms, giving a push input, shifts the state back to Locked.
 *
 * @see https://en.wikipedia.org/wiki/Finite-state_machine
 *
 * @author maso<mostafa.barmshory@dpq.co.ir>
 *        
 */
class TurnstileTest extends TestCase
{

    var $machine = null;

    /**
     *
     * @beforeClass
     */
    public static function setupApplication()
    {
        Pluf::start(__DIR__ . '/conf/config.php');
        $m = new Pluf_Migration();
        $m->install();
    }

    /**
     *
     * @afterClass
     */
    public static function deleteApplication()
    {
        $m = new Pluf_Migration();
        $m->uninstall();
    }

    /**
     *
     * @before
     */
    public function instance()
    {
        // create maching
        $this->machine = new Machine();
        $this->assertTrue(isset($this->machine));

        // Machine
        $states = array(
            Machine::STATE_UNDEFINED => array(
                'next' => 'Locked'
            ),
            // State
            'Locked' => array(
                // Transaction or event
                'coin' => array(
                    'next' => 'Unlocked',
                    // 'action' => array(
                    // 'Spa_SPA_Manager_Simple',
                    // 'checkUpdate'
                    // ),
                    // 'preconditions' => array(
                    // 'User_Precondition::isOwner'
                    // ),
                    // client side
                    'title' => '',
                    'description' => '',
                    'properties' => array()
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
        $initState = 'Locked';
        $this->machine->setStates($states)->setInitialState($initState);
    }

    /**
     *
     * @test
     */
    public function initObject()
    {
        $object = new Book();
        $this->machine->apply($object, 'push');
        $this->assertTrue($object->state === 'Locked');
    }

    /**
     *
     * @test
     */
    public function validObject()
    {
        $object = new Book();
        $object->state = 'Locked';
        $this->machine->apply($object, 'push');
        $this->assertTrue($object->state === 'Locked');
    }
}