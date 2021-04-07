<?php
namespace Pluf\Workflow\Builder;

/**
 * On clause builder which is used to build transition condition
 *
 * @author Henry.He
 */
interface On extends When
{

    /**
     * Add condition for the transition
     *
     * @param
     *            condition transition condition
     * @return When clause builde r
     */
    function when($condition): When;

    /**
     * Add condition for the transition
     *
     * @param
     *            expression mvel expression
     * @return When clause builder
     */
    function whenMvel(String $expression): When;
}

