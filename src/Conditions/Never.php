<?php
namespace Pluf\Workflow\Conditions;

use Pluf\Workflow\Condition;

class Never implements Condition
{

    public function name(): string
    {
        return 'Never';
    }

    public function isSatisfied($context): bool
    {
        return false;
    }
}

