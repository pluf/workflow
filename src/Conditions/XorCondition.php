<?php
namespace Pluf\Workflow\Conditions;

use Pluf\Workflow\Condition;

class XorCondition implements Condition
{
    public function __construct(
        public Condition $a, 
        public Condition $b){ }
    public function name(): string
    {
        return 'Xor';
    }

    public function isSatisfied($context): bool
    {
        return $this->a->isSatisfied($context) ^ $this->b->isSatisfied($context);
    }

}

