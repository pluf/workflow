<?php
namespace Pluf\Workflow;

use function PHPUnit\Framework\isEmpty;

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
    
//     private bool $isTestEvent;
    
//     private static ThreadLocal<Stack<StateMachineContext>> contextContainer = new ThreadLocal<Stack<StateMachineContext>>() {
//         protected Stack<StateMachineContext> initialValue() {
//             return new Stack<StateMachineContext>();
//         }
//     };
    
    public function __construct(
        private $stateMachine, 
        private bool $testEvent = false) {    }
    
    
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
    
    public static function currentInstance() {
        return self::$currentInstance;
    }
    
    public static function isTestEvent() :bool{
//         return contextContainer.get().size()>0  ? contextContainer.get().peek().isTestEvent : false;
        if(isEmpty(self::$stack)){
            return false;
        }
        $instance = end(self::$stack);
        return $instance->testEvent;
    }
}

