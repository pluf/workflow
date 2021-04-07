<?php
namespace Pluf\Workflow\Builder;

/**
 * Created by kailianghe on 7/12/14.
 */
interface AndState
{

    /**
     * Specify mutual transition events
     *
     * @param
     *            fromEvent cause transition from fromState to toState
     * @param
     *            toEvent cause transition from toState to fromState
     * @return on clause builder
     */
    function onMutual($fromEvent, $toEvent): On;
}