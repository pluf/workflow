<?php
namespace Pluf\Workflow\Conditions;

use Pluf\Workflow\Condition;

class Always implements Condition
{

    public function name(): string
    {
        return 'Always';
    }

    public function isSatisfied($context): bool
    {
        return true;
    }
}

