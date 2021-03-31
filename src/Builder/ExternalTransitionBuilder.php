<?php
namespace Pluf\Workflow\Builder;

/**
 * External transition builder which is used to build a external transition.
 *
 * @author Henry.He
 */
interface ExternalTransitionBuilder
{

    /**
     * Build transition source state.
     *
     * @param
     *            stateId id of state
     * @return from clause builder
     */
    function from($stateId): From;
}