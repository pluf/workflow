<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\ActionExecutionService;
use Pluf\Workflow\ImmutableState;
use Pluf\Workflow\StateContext;
use Pluf\Workflow\StateMachine;
use Pluf\Workflow\StateMachineData;
use Pluf\Workflow\TransitionResult;

class StateContextImpl implements StateContext
{

    private StateMachine $stateMachine;

    private StateMachineData $stateMachineData;

    private ImmutableState $sourceState;

    private $context;

    private $event;

    private ?TransitionResult $result;

    private ActionExecutionService $executor;

    public function __construct(StateMachine $stateMachine, StateMachineData $stateMachineData, ?ImmutableState $sourceState, $event, $context, ?TransitionResult $result, ActionExecutionService $executor)
    {
        $this->stateMachine = $stateMachine;
        $this->stateMachineData = $stateMachineData;
        $this->sourceState = $sourceState;
        $this->event = $event;
        $this->context = $context;
        $this->result = $result;
        $this->executor = $executor;
    }

    public function getStateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    public function getSourceState(): ImmutableState
    {
        return $this->sourceState;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function getResult(): TransitionResult
    {
        return $this->result;
    }

    public function getExecutor(): ActionExecutionService
    {
        return $this->executor;
    }

    public function getStateMachineData(): StateMachineData
    {
        return $this->stateMachineData;
    }
}