<?php
namespace Pluf\Workflow\Builder;

interface DeferBoundActionFrom
{

    /**
     * Build transition target state and return to clause builder
     *
     * @param
     *            stateId id of state
     * @return To clause builder
     */
    function to($stateId): DeferBoundActionTo;

    function toAny(): DeferBoundActionTo;
}

