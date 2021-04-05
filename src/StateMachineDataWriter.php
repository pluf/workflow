<?php
namespace Pluf\Workflow;

interface StateMachineDataWriter
{

    function setIdentifier(string $id);

    /**
     * Write current state of state machine data to provided state id
     *
     * @param
     *            currentStateId
     */
    function setCurrentState($currentStateId);

    /**
     * Write last state of state machine data to provided state id
     *
     * @param
     *            lastStateId
     */
    function setLastState($lastStateId);

    /**
     * Write initial state of state machine data to provided state id
     *
     * @param
     *            initialStateId
     */
    function setInitialState($initialStateId);

    /**
     * Write start context of state machine
     *
     * @param
     *            context start context of state machine
     */
    function setStartContext($context);

    /**
     * Set last active child state of parent state
     *
     * @param
     *            parentStateId
     *            id of parent state
     * @param
     *            childStateId
     *            id of child state
     */
    function setLastActiveChildStateFor($parentStateId, $childStateId);

    /**
     * Write provided sub state for provided parent state
     *
     * @param
     *            parentStateId
     * @param
     *            subStateId
     */
    function setSubStateFor($parentStateId, $subStateId);

    /**
     * Remove provide sub state under provided parent state
     *
     * @param
     *            parentStateId
     * @param
     *            subStateId
     */
    function removeSubState($parentStateId, $subStateId);

    /**
     * Remove all sub states under provider parent state
     *
     * @param
     *            parentStateId
     */
    function removeSubStatesOn($parentStateId);

    /**
     * Write type of state machine
     *
     * @param
     *            stateMachineType
     */
    function setTypeOfStateMachine(string $stateMachineType): void;

    /**
     * Write type of state
     *
     * @param
     *            stateClass
     */
    function setTypeOfState(string $stateClass): void;

    /**
     * Write type of event
     *
     * @param
     *            eventClass
     */
    function setTypeOfEvent(string $eventClass): void;

    /**
     * Write type of context
     *
     * @param
     *            contextClass
     */
    function setTypeOfContext(?string $contextClass): void;

    /**
     * Write linked state data on specified linked state
     *
     * @param
     *            linkedState
     *            specified linked state
     * @param
     *            linkStateData
     *            linked state data
     */
    function setLinkedStateDataOn($linkedState, StateMachineDataReader $linkStateData);
}

