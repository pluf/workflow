<?php
namespace Pluf\Workflow;

interface MutableTransition extends ImmutableTransition
{

    function setSourceState(ImmutableState $state): void;

    function setTargetState(ImmutableState $state): void;

    function addAction($newAction): void;

    function addActions(array $newActions): void;

    function setCondition(Condition $condition): void;

    function setEvent($event): void;

    function setType(string $type): void;

    function setPriority(int $priority): void;
}

