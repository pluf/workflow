<?php
namespace Pluf\Workflow;

/**
 * This is a stack of statemachin to execute hericicaly
 *
 * @author maso
 *        
 */
class StateMachineContext
{

    private static $currentInstance;

    private static array $stack = [];

    private $stateMachine;

    private bool $testEvent = false;

    /**
     * Creates a new instance
     * 
     * @param StateMachine $stateMachine
     * @param bool $testEvent
     */
    public function __construct(StateMachine $stateMachine, bool $testEvent = false)
    {
        $this->stateMachine = $stateMachine;
        $this->testEvent = $testEvent;
    }

    public static function set($instance, bool $testEvent = false)
    {
        if ($instance == null) {
            // contextContainer.get().pop();
            array_pop(self::$stack);
        } else {
            // contextContainer.get().push(new StateMachineContext(instance, isTestEvent));
            array_push(self::$stack, $testEvent);
        }
    }

    public static function currentInstance()
    {
        return self::$currentInstance;
    }

    public static function isTestEvent(): bool
    {
        // return contextContainer.get().size()>0 ? contextContainer.get().peek().isTestEvent : false;
        if (empty(self::$stack)) {
            return false;
        }
        $instance = end(self::$stack);
        return $instance->testEvent;
    }
}

