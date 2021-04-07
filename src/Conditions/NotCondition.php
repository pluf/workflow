<?php
namespace Pluf\Workflow\Conditions;

use Pluf\Workflow\Condition;

class NotCondition implements Condition
{

    public Condition $b;

    public function __construct(Condition $b)
    {
        $this->b = $b;
    }

    public function name(): string
    {
        return 'Not';
    }

    public function isSatisfied($context): bool
    {
        return ! $this->b->isSatisfied($context);
    }
}

 