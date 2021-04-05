<?php
namespace Pluf\Workflow\Attributes;

use Attribute;
use Pluf\Workflow\StateCompositeType;
use Pluf\Workflow\HistoryType;

#[Attribute(Attribute::TARGET_CLASS)]
class State
{
    public function __construct(
        public ?string $parent = null,
        public ?string $name = null,
        public ?string $alias = null,
        public ?string $entryCallMethod = null,
        public ?string $exitCallMethod = null,
        public bool $initialState = false,
        public bool $finalState = false,
        public string $historyType = HistoryType::NONE,
        public string $compositeType = StateCompositeType::SEQUENTIAL
        ){}
}

