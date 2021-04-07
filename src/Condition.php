<?php
namespace Pluf\Workflow;

/**
 * A constraint which must evaluate to true after the trigger occurs in order for the transition to complete.
 *
 * The core engine of workflow call the constractur with DI, so inject all dependencies on constractor.
 *
 * @author maso
 */
interface Condition
{

    /**
     *
     * @param
     *            context context object
     * @return bool whether the context satisfied current condition
     */
    public function isSatisfied($context): bool;

    public function name(): string;
}

