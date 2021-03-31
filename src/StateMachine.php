<?php
namespace Pluf\Workflow;

/**
 * Interface for finite state machine.
 *
 * Here is list of all posible states of a FSM
 *
 * - INITIALIZED
 * - IDLE
 * - BUSY
 * - TERMINATED
 * - ERROR
 *
 * @author Henry.He
 *        
 */
interface StateMachine
{

    /**
     * Fires the specified event
     *
     * @param
     *            event the event
     * @param
     *            context external context
     *            
     * @param
     *            testEvent if the event is for test
     */
    function fire($event, $context, bool $testEvent = false): void;

    /**
     * Fires event with context immediately, if current state machine is busy, the next processing event
     * is this event.
     *
     * @param
     *            event the event
     * @param
     *            context external context
     */
    function fireImmediate($event, $context): void;

    /**
     * Test transition result under circumstance
     *
     * @param
     *            event test event
     * @param
     *            context text context
     * @return mixed test transition result
     */
    function test($event, $context);

    /**
     *
     * @param
     *            event test event
     * @return true is passed in event is acceptable otherwise return false
     */
    function canAccept($event): bool;

    /**
     * Start state machine under external context
     *
     * @param
     *            context external context
     */
    function start($context): void;

    /**
     * Terminate state machine under external context
     *
     * @param
     *            context external context
     */
    function terminate($context): void;

    /**
     *
     * - INITIALIZED
     * - IDLE
     * - BUSY
     * - TERMINATED
     * - ERROR
     *
     * @return string current status of state machine
     */
    function getStatus(): string;

//     /**
//      *
//      * @return type-safe state machine instance
//      */
//     function getThis();

    /**
     *
     * @return mixed current state id of state machine
     */
    function getCurrentState();

    /**
     *
     * @return mixed last active state id of state machine
     */
    function getLastState();

    /**
     *
     * @return mixed id of state machine initial state
     */
    function getInitialState();

    /**
     *
     * @param
     *            parentStateId id of parent state
     * @return mixed last active child state of the parent state
     */
    function getLastActiveChildStateOf($parentStateId);

    /**
     *
     * @param
     *            parentStateId
     * @return mixed sub state of parallel state
     */
    function getSubStatesOn($parentStateId);

    /**
     *
     * @return ImmutableState current raw state of state machine
     */
    function getCurrentRawState(): ImmutableState;

    /**
     *
     * @return ImmutableState last active raw state of state machine
     */
    function getLastRawState(): ImmutableState;

    /**
     *
     * @return ImmutableState initial raw state of state machine
     */
    function getInitialRawState(): ImmutableState;

    function getRawStateFrom($stateId): ImmutableState;

    /**
     *
     * @reurn array lis of all states
     */
    function getAllStates(): array;

    /**
     * list of all raw states
     */
    function getAllRawStates(): array;

    function typeOfContext(): string;

    function typeOfEvent(): string;

    function typeOfState(): string;

    function getLastException();

    function getIdentifier(): string;

    function getDescription(): string;

    function isRemoteMonitorEnabled(): bool;

    function isStarted(): bool;

    function isTerminated(): bool;

    function isError(): bool;

    // ------------------------------------------------------------------------------
    // IO
    // ------------------------------------------------------------------------------
    /**
     * Dump current state machine data.
     * This operation can only be done when state machine status is
     * {@link StateMachineStatus#IDLE}, otherwise null will be returned.
     *
     * @return mixed dumped state machine data reader
     */
    function dumpSavedData();

    /**
     * Load saved data for current state machine.
     * The operation can only be done when state machine
     * status is {@link StateMachineStatus#INITIALIZED} or {@link StateMachineStatus#TERMINATED}.
     *
     * @param
     *            savedData provided saved data
     * @return true if load saved data success otherwise false
     */
    function loadSavedData(StateMachineDataReader $savedData): bool;

    function exportXMLDefinition(bool $beautifyXml): string;
}
    
    /*
//     void addDeclarativeListener(Object listener);
//     void removeDeclarativeListener(Object listener);
//     void addStateMachineListener(StateMachineListener<T, S, E, C> listener);
//     void removeStateMachineListener(StateMachineListener<T, S, E, C> listener);
//     void addStartListener(StartListener<T, S, E, C> listener);
//     void removeStartListener(StartListener<T, S, E, C> listener);
//     void addTerminateListener(TerminateListener<T, S, E, C> listener);
//     void removeTerminateListener(TerminateListener<T, S, E, C> listener);
//     void addStateMachineExceptionListener(StateMachineExceptionListener<T, S, E, C> listener);
//     void removeStateMachineExceptionListener(StateMachineExceptionListener<T, S, E, C> listener);
//     void addTransitionBeginListener(TransitionBeginListener<T, S, E, C> listener);
//     void removeTransitionBeginListener(TransitionBeginListener<T, S, E, C> listener);
//     void addTransitionCompleteListener(TransitionCompleteListener<T, S, E, C> listener);
//     void removeTransitionCompleteListener(TransitionCompleteListener<T, S, E, C> listener);
//     void addTransitionExceptionListener(TransitionExceptionListener<T, S, E, C> listener);
//     void removeTransitionExceptionListener(TransitionExceptionListener<T, S, E, C> listener);
//     void addTransitionDeclinedListener(TransitionDeclinedListener<T, S, E, C> listener);
//     void removeTransitionDeclinedListener(TransitionDeclinedListener<T, S, E, C> listener);
//     void removeTransitionDecleindListener(TransitionDeclinedListener<T, S, E, C> listener);
//     void addTransitionEndListener(TransitionEndListener<T, S, E, C> listener);
//     void removeTransitionEndListener(TransitionEndListener<T, S, E, C> listener);
//     void addExecActionListener(BeforeExecActionListener<T, S, E, C> listener);
//     void removeExecActionListener(BeforeExecActionListener<T, S, E, C> listener);
 */

