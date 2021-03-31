<?php
namespace Pluf\Workflow\Actions;

use Pluf\Workflow\IllegalStateException;

/**
 * An invokable to guard the final states from transactions
 * 
 * @author maso
 *
 */
class FinalStateGuardAction /* implimplements  NamedItem */
{

    public function __invoke($from, $to, $event, $context, $stateMachine)
    {
        throw new IllegalStateException("Final state cannot be exited anymore.");
    }

    public function name(): string
    {
        return "__FINAL_STATE_ACTION_GUARD";
    }
}

