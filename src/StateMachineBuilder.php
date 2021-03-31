<?php
namespace Pluf\Workflow;

use Pluf\Workflow\Builder\ExternalTransitionBuilder;
use Pluf\Workflow\Builder\MultiTransitionBuilder;
use Pluf\Workflow\Builder\DeferBoundActionBuilder;
use Pluf\Workflow\Builder\LocalTransitionBuilder;
use Pluf\Workflow\Builder\InternalTransitionBuilder;
use Pluf\Workflow\Builder\EntryExitActionBuilder;

/**
 * State machine builder API.
 *
 * @author Henry.He
 */
interface StateMachineBuilder
{

    /**
     * Start to build external transition
     * Create external transition builder with priority
     *
     * @param
     *            priority external transition priority
     * @return ExternalTransitionBuilder external transition builder with priority
     */
    function externalTransition(int $priority = 0): ExternalTransitionBuilder;

    /**
     * Create multiple external transitions builder with default priority
     * Create multiple external transitions builder with priority
     *
     * @param
     *            priority external transitions priority
     * @return MultiTransitionBuilder multiple external transitions builder
     */
    function externalTransitions(int $priority = 0): MultiTransitionBuilder;

    /**
     * Start to build external transition, same as externalTransition
     * Same as externalTransition
     *
     * @param
     *            priority transition priority
     * @return ExternalTransitionBuilder External transition builder
     */
    function transition(int $priority = 0): ExternalTransitionBuilder;

    /**
     * The same as <code>externalTransitions</code>
     * the same as <code>externalTransitions<code/>
     *
     * @param
     *            priority external transitions priority
     * @return MultiTransitionBuilder multiple external transitions builder
     */
    function transitions(int $priority = 0): MultiTransitionBuilder;

    /**
     * Create defer bound action builder
     *
     * @return DeferBoundActionBuilder defer bound action builder
     */
    function transit(): DeferBoundActionBuilder;

    /**
     * Create local transition builder with priority
     *
     * @param
     *            priority local transition priority
     * @return LocalTransitionBuilder local transition builder
     */
    function localTransition(int $priority = 0): LocalTransitionBuilder;

    /**
     * Create multiple local transitions builder with priority
     *
     * @param
     *            priority local transition priority
     * @return MultiTransitionBuilder local transition builder
     */
    function localTransitions(int $priority = 0): MultiTransitionBuilder;

    /**
     * Create internal transition builder with priority
     *
     * @param
     *            priority internal transition priority
     * @return InternalTransitionBuilder internal transition
     */
    function internalTransition(int $priority = 0): InternalTransitionBuilder;

    /**
     * Define a new state in state machine model
     *
     * @param
     *            stateId id of new state
     * @return MutableState defined new mutable state
     */
    function defineState($stateId): MutableState;

    /**
     * Define a final state in state machine model
     *
     * @param
     *            stateId id of final state
     * @return MutableState defined final state
     */
    function defineFinalState($stateId): MutableState;

    /**
     * Define a linked state
     *
     * @param
     *            stateId id of linked state
     * @param
     *            linkedStateMachineBuilder linked state machine builder
     * @param
     *            initialLinkedState initial linked state
     * @param
     *            extraParams additional parameters used to create linked state machine
     * @return MutableState linked state
     */
    function defineLinkedState($stateId, $linkedStateMachineBuilder, $initialLinkedState, $extraParams): MutableState;

    /**
     * Define a timed state
     *
     * @param
     *            stateId state id
     * @param
     *            initialDelay initial delay ms
     * @param
     *            timeInterval time period if null not repeat
     * @param
     *            autoEvent
     * @param
     *            autoContext
     * @return MutableState timed state
     */
    function defineTimedState($stateId, int $initialDelay, int $timeInterval, $autoEvent, $autoContext): MutableState;

    /**
     * Define sequential child states whose hierarchy type is default set to NONE on parent state
     *
     * @param
     *            parentStateId id of parent state
     * @param
     *            childStateIds child states id of parent state. The first child state will be used as initial child state of parent state.
     * @param
     *            historyType history type of parent state
     */
    function defineSequentialStatesOn($parentStateId, $childStateIds, ?HistoryType $historyType = null): void;

    /**
     * Define sequential child states on parent state without initial state
     *
     * @param
     *            parentStateId id of parent state
     * @param
     *            childStateIds child states id of parent state
     * @param
     *            childStateIds child states id of parent state
     */
    function defineNoInitSequentialStatesOn($parentStateId, $childStateIds, ?HistoryType $historyType = null): void;

    /**
     * Define sequential child states on parent state.
     * For parallel state the history type always be none.
     *
     * @param
     *            parentStateId id of parent state
     * @param
     *            childStateIds child states id of parent state. The first child state will be used as initial child state of parent state.
     */
    function defineParallelStatesOn($parentStateId, $childStateIds): void;

    /**
     * Define event for parallel transition finished
     *
     * @param
     *            finishEvent
     */
    function defineFinishEvent($finishEvent): void;

    /**
     * Define event for state machine started
     *
     * @param
     *            startEvent
     */
    function defineStartEvent($startEvent): void;

    /**
     * Define event for state machine terminated
     *
     * @param
     *            terminateEvent
     */
    function defineTerminateEvent($terminateEvent): void;

    /**
     * Define on entry actions for state
     *
     * @param
     *            stateId the id of state
     * @return EntryExitActionBuilder the builder to build state on entry actions
     */
    function onEntry($stateId): EntryExitActionBuilder;

    /**
     * Define on exit actions for state
     *
     * @param
     *            stateId the id of state
     * @return EntryExitActionBuilder the builder to build state on exit actions
     */
    function onExit($stateId): EntryExitActionBuilder;

    // /**
    // * Create a new state machine instance
    // * @param initialStateId initial state id
    // * @return mixed new state machine instance
    // */
    // function newStateMachine($initialStateId) ;

    /**
     * Create new state machine instance according to state machine definition
     *
     * @param
     *            initialStateId the id of state machine initial state
     * @param
     *            configuration configuration for state machine
     * @param
     *            extraParams other parameters for instantiate state machine, a map to use in instance
     * @return StateMachine new state machine
     */
    function newStateMachine($initialStateId,  ?StateMachineConfiguration $configuration = null, array $extraParams = null) : StateMachine;

    /**
     * Set default state machine configuration for state machine instance created by this builder
     *
     * @param
     *            configure state machine default configuration
     */
    function setStateMachineConfiguration(StateMachineConfiguration $configure): void;
}