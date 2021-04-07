<?php
namespace Pluf\Workflow;

use ArrayObject;

interface StateMachineDataReader
{

    /**
     *
     * @return string state machine identifier
     */
    function getIdentifier(): string;

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
     *            parentStateId
     *            id of parent state
     * @return mixed last active child state of the parent state
     */
    function getLastActiveChildStateOf($parentStateId);

    /**
     *
     * @return mixed start context of state machine
     */
    function getStartContext();

    /**
     *
     * @return array all the active parent states
     */
    function getActiveParentStates(): array;

    /**
     *
     * @param
     *            parentStateId
     * @return array sub state of parallel state
     */
    function getSubStatesOn($parentStateId): array;

    /**
     *
     * @returnImmutableState  current raw state of state machine
     */
    function getCurrentRawState(): ?ImmutableState;

    /**
     *
     * @return ImmutableState last active raw state of state machine
     */
    function getLastRawState(): ImmutableState;

    /**
     *
     * @return ImmutableState initial raw state of state machine
     */
    function getInitialRawState(): ?ImmutableState;

    /**
     *
     * @param
     *            stateId
     *            the identify of state
     * @return ImmutableState raw state of the same state identify
     */
    function getRawStateFrom($stateId): ?ImmutableState;

    /**
     *
     * @return array all the parallel states
     */
    function getParallelStates(): array;

    /**
     * The state machin implementation type
     * 
     * Only one class is considered as implementationtion. All functional part of a 
     * state machine is implemented in a class.
     * @return string type of state machine
     */
    function getTypeOfStateMachine(): ?string;

    /**
     *
     * @return string type of state
     */
    function getTypeOfState(): string;

    /**
     *
     * @return string type of event
     */
    function getTypeOfEvent(): string;

    /**
     *
     * @return string type of context
     */
    function getTypeOfContext(): string;

    /**
     *
     * @return array all the raw states defined in the state machine
     */
    function getRawStates(): array;

    /**
     *
     * @return array all the states defined in the state machine
     */
    function getStates(): array;

    /**
     *
     * @return array all linked states
     */
    function getLinkedStates(): array;

    function getOriginalStates(): ArrayObject;

    function getLinkedStateDataOf($linkedState): StateMachineDataReader;
}

