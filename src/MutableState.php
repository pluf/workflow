<?php
namespace Pluf\Workflow;

interface MutableState extends ImmutableState
{

    function addTransitionOn($event): MutableTransition;

    function addEntryAction($newAction): void;

    function addEntryActions(array $newActions): void;

    function addExitAction($newAction): void;

    function addExitActions(array $newActions): void;

    function setParentState(MutableState $parent): void;

    function addChildState(MutableState $childState): void;

    function setInitialState(MutableState $childInitialState): void;

    function setLevel(int $level): void;

    function setHistoryType(string $historyType): void;

    function setFinal(bool $isFinal): void;

    /**
     * See StateCompositeType
     *
     * @param string $compositeType
     */
    function setCompositeType(string $compositeType): void;

    function prioritizeTransitions(): void;
}

