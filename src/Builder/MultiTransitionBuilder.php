<?php
namespace Pluf\Workflow\Builder;

/**
 * Created by kailianghe on 7/12/14.
 */
interface MultiTransitionBuilder
{

    /**
     * Build transition source state.
     *
     * @param
     *            stateId id of state
     * @return multi from clause builder
     */
    function from($stateId): MultiFrom;

    /**
     * Build transition source states.
     *
     * @param
     *            stateIds id of states
     * @return single from clause builder
     */
    function fromAmong($stateIds): From;

    /**
     * Build mutual transitions between two state
     *
     * @param
     *            fromStateId from state id
     * @return between clause builder
     */
    function between($fromStateId): Between;
}