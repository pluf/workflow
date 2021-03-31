<?php
namespace Pluf\Workflow\Builder;

/**
 * When clause builder which is used to install actions during transition
 *
 * @author Henry.He
 */
interface When
{

    /**
     * Define action to be performed during transition
     * Define actions to be performed during transition.
     * When used in multiple transition builder,
     * the actions will be sequentially assigned to each transition. If actions size is less than
     * transitions size, the last action will be assigned to the rest of transitions. null value in
     * actions will be skipped which means no action will be assigned to corresponding transition.
     *
     * @param
     *            actions performed actions
     * @param
     *            action performed action
     */
    function perform($actions);

    /**
     * Define mvel action to be performed during transition
     *
     * @param
     *            expression mvel expression
     */
    function evalMvel(String $expression);

    /**
     * Define action method to be called during transition.
     * When used in multiple transition builder,
     * the method name can be joined by '|'. Each method action will be sequentially assigned to each
     * transition. If actions size is less than transitions size, the last method action will be assigned
     * to the rest of transitions. '_' represent a place holder which means no method action will be
     * assigned to corresponding transition.
     *
     * @param
     *            methodName method name
     */
    function callMethod(String $methodName);
}