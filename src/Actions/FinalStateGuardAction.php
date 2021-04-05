<?php
namespace Pluf\Workflow\Actions;

use Pluf\Workflow\Exceptions\IllegalStateException;

/**
 * An invokable to guard the final states from transactions
 *
 * @author maso
 *        
 */
class FinalStateGuardAction extends AbstractAction
{

    public function __construct(string $name = "__FINAL_STATE_ACTION_GUARD", int $weight = 1)
    {
        parent::__construct($name, $weight);
    }

    public function __invoke($from, $to, $event, $context, $stateMachine)
    {
        throw new IllegalStateException("Final state cannot be exited anymore.");
    }
}

