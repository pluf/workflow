<?php
namespace Pluf\Workflow\Attributes;

use Pluf\Workflow\TransitionType;

#[Attribute]
class Transit
{

    public ?string $from;

    public ?string $to;

    public ?string $on;

    public bool $targetFinal = false;

    public string $when = 'always';

    public ?string $whenMvel = null;

    public string $typd = TransitionType::EXTERNAL;

    public ?string $callMethod = null;

    public int $priority = 1;

    public function __construct(?string $from, ?string $to, ?string $on, bool $targetFinal = false, string $when = 'always', ?string $whenMvel = null, string $typd = TransitionType::EXTERNAL, ?string $callMethod = null, int $priority = 1)
    {
        $this->from = $from;
        $this->to = $to;
        $this->on = $on;
        $this->targetFinal = $targetFinal;
        $this->when = $when;
        $this->whenMvel = $whenMvel;
        $this->typd = $typd;
        $this->callMethod = $callMethod;
        $this->priority = $priority;
    }
}

