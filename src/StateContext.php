<?php
namespace Pluf\Workflow;

interface  StateContext
{
    
    /**
     * @return StateMachine  current state machine object
     */
    public function getStateMachine(): StateMachine;
    
    /**
     * @return StateMachineData state machine data
     */
    public function getStateMachineData(): StateMachineData;
    
    /**
     * @return ImmutableState source state of state machine
     */
    public function getSourceState(): ImmutableState;
    
    /**
     * @return mixed external context object
     */
    public function getContext();
    
    /**
     * @return mixed event
     */
    public function getEvent();
    
    /**
     * @return TransitionResult transition result
     */
    public function getResult(): TransitionResult;
    
    /**
     * @return action executor
     */
    public function getExecutor(): ActionExecutionService;
}

