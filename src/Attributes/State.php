<?php
namespace Pluf\Workflow\Attributes;

use Attribute;
use Pluf\Workflow\StateCompositeType;
use Pluf\Workflow\HistoryType;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class State
{

    public ?string $parent = null;

    public ?string $name = null;

    public ?string $alias = null;

    public ?string $entryCallMethod = null;

    public ?string $exitCallMethod = null;

    public bool $initialState = false;

    public bool $finalState = false;

    public string $historyType = HistoryType::NONE;

    public string $compositeType = StateCompositeType::SEQUENTIAL;

    public function __construct(?string $parent = null, ?string $name = null, ?string $alias = null, ?string $entryCallMethod = null, ?string $exitCallMethod = null, bool $initialState = false, bool $finalState = false, string $historyType = HistoryType::NONE, string $compositeType = StateCompositeType::SEQUENTIAL)
    {
        $this->parent = $parent;
        $this->name = $name;
        $this->alias = $alias;
        $this->entryCallMethod = $entryCallMethod;
        $this->exitCallMethod = $exitCallMethod;
        $this->initialState = $initialState;
        $this->finalState = $finalState;
        $this->historyType = $historyType;
        $this->compositeType = $compositeType;
    }
}