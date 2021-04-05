<?php
namespace Pluf\Workflow\Attributes;
use Attribute;

#[Attribute]
class StateMachineDefinition
{
    public ?string $location = null;
}

