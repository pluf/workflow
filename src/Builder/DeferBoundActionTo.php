<?php
namespace Pluf\Workflow\Builder;

interface DeferBoundActionTo
{

    /**
     * Build transition event
     *
     * @param
     *            event transition event
     * @return On clause builder
     */
    function on($event): On;

    function onAny(): On;
}

