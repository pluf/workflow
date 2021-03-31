<?php
namespace Pluf\Workflow\Builder;

/**
 * From clause builder which is used to build transition target state.
 *
 * @author Henry.He
 */
interface From
{

    /**
     * Build transition target state and return to clause builder
     *
     * @param
     *            stateId id of state
     * @return To clause builder
     */
    function to($stateId): To;

    /**
     * Builder transition target state as final state and return to clause builder
     *
     * @param
     *            stateId id of state
     * @return To clause builder
     */
    function toFinal($stateId): To;
}
