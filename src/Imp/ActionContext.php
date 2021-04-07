<?php
namespace Pluf\Workflow\Imp;

use phpDocumentor\Reflection\Types\Callable_;

/**
 * Action context to run
 *
 * Action must be an invokable action.
 *
 * @author maso
 *        
 */
class ActionContext
{

    /**
     * Invokable to run
     *
     * @var Callable_
     */
    public $action;

    public $from;

    public $to;

    public $event;

    public $context;

    public StateMachineImpl $stateMachine;

    public int $position;

    public function __construct($action, $from, $to, $event, $context, $stateMachine, int $position)
    {
        $this->action = $action;
        $this->from = $from;
        $this->to = $to;
        $this->event = $event;
        $this->context = $context;
        $this->stateMachine = $stateMachine;
        $this->position = $position;
    }

    public static function get($action, $from, $to, $event, $context, $stateMachine, int $position): ActionContext
    {
        return new ActionContext($action, $from, $to, $event, $context, $stateMachine, $position);
    }
}

