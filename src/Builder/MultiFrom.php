<?php
namespace Pluf\Workflow\Builder;

/**
 * Created by kailianghe on 7/12/14.
 */
interface MultiFrom
{

    /**
     * Build transition target states and return to clause builder
     *
     * @param
     *            stateIds id of states
     * @return To clause builder
     */
    function toAmong($stateIds): MultiTo;
}