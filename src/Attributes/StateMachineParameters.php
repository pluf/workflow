<?php
namespace Pluf\Workflow\Attributes;
use Attribute;

#[Attribute]
class StateMachineParameters
{
    public ?string $stateType= null;
    public ?string $eventType= null;
    public ?string $contextType = null;
}

