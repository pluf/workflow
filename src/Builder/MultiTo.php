<?php
namespace Pluf\Workflow\Builder;

/**
 * Created by kailianghe on 7/12/14.
 */
interface MultiTo
{

    /**
     * Build transition event
     *
     * @param
     *            events transition event
     * @return On clause builder
     */
    function onEach($events): On;
}