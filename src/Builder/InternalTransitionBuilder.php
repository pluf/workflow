<?php
namespace Pluf\Workflow\Builder;

/**
 * Internal transition builder which is used to build a internal transition
 *
 * @author Henry.He
 */
interface InternalTransitionBuilder extends ExternalTransitionBuilder
{

    /**
     * Build a internal transition
     *
     * @param
     *            stateId id of transition
     * @return To clause builder
     */
    function within($stateId): To;
}

