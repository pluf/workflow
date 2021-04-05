<?php
namespace Pluf\Workflow\Conditions;

use Pluf\Workflow\Condition;

class XorCondition implements Condition
{

    public Condition $a;

    public Condition $b;

    public function __construct(Condition $a, Condition $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    public function name(): string
    {
        return 'Xor';
    }

    public function isSatisfied($context): bool
    {
        return $this->a->isSatisfied($context) ^ $this->b->isSatisfied($context);
    }
}

