<?php
namespace Pluf\Workflow\Builder;

/**
 * On clause builder which is used to build transition event
 *
 * @author Henry.He
 */
interface To
{

    /**
     * Build transition event
     * @param event transition event
     * @return On clause builder
     */
    function on($event): On;
}
