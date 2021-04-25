<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\ActionExecutionService;
use Pluf\Workflow\ImmutableState;
use Pluf\Workflow\StateContext;
use Pluf\Workflow\StateMachine;
use Pluf\Workflow\StateMachineData;
use Pluf\Workflow\TransitionResult;

/**
 * State context implementation
 *
 * @author maso
 *        
 */
class StateContextImpl implements StateContext
{

    public StateMachine $stateMachine;

    public StateMachineData $stateMachineData;

    public ImmutableState $sourceState;

    public $context;

    public $event;

    public ?TransitionResult $result;

    public ActionExecutionService $executor;

    /**
     * Creates a new state context
     *
     * @param StateMachine $stateMachine
     * @param StateMachineData $stateMachineData
     * @param ImmutableState $sourceState
     * @param mixed $event
     * @param mixed $context
     * @param TransitionResult $result
     * @param ActionExecutionService $executor
     */
    public function __construct(StateMachine $stateMachine, StateMachineData $stateMachineData, ?ImmutableState $sourceState, $event, 
        $context = null, 
        ?TransitionResult $transitionResult= null, 
        ?ActionExecutionService $actionExecutionService = null)
    {
        $this->stateMachine = $stateMachine;
        $this->stateMachineData = $stateMachineData;
        $this->sourceState = $sourceState;
        $this->event = $event;
        $this->context = $context;
        $this->result = $transitionResult;
        $this->executor = $actionExecutionService;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateContext::getStateMachine()
     */
    public function getStateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateContext::getSourceState()
     */
    public function getSourceState(): ImmutableState
    {
        return $this->sourceState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateContext::getContext()
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateContext::getEvent()
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateContext::getResult()
     */
    public function getResult(): TransitionResult
    {
        return $this->result;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateContext::getExecutor()
     */
    public function getExecutor(): ActionExecutionService
    {
        return $this->executor;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateContext::getStateMachineData()
     */
    public function getStateMachineData(): StateMachineData
    {
        return $this->stateMachineData;
    }
}