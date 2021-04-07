<?php
namespace Pluf\Workflow\Builder;

/**
 * Created by kailianghe on 7/12/14.
 */
interface Between
{

    /**
     * Build mutual transitions between state
     *
     * @param
     *            toStateId to state id
     * @return AndState and clause builder
     */
    function and($toStateId): AndState;
}

