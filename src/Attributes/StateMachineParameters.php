<?php
namespace Pluf\Workflow\Attributes;

use Attribute;

# [Attribute(Attribute::TARGET_CLASS)]
class StateMachineParameters
{

    public ?string $stateType = 'string';

    public ?string $eventType = 'string';

    public ?string $contextType = null;

    public function __construct(string $stateType = 'string', string $eventType = 'string', ?string $contextType = null)
    {
        $this->stateType = $stateType;
        $this->eventType = $eventType;
        $this->contextType = $contextType;
    }
}

