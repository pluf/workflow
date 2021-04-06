<?php
namespace Pluf\Workflow\Attributes;

use Attribute;
use Pluf\Workflow\TransitionType;
use Pluf\Workflow\Conditions\Always;
use Pluf\Workflow\Conditions\Never;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class Transit
{

    public ?string $from;

    public ?string $to;

    public ?string $on;

    public bool $targetFinal = false;

    public string $when = 'Always';

    public ?string $whenMvel = null;

    public string $type = TransitionType::EXTERNAL;

    public ?string $callMethod = null;

    public int $priority = 1;

    public function __construct(?string $from, ?string $to, ?string $on, bool $targetFinal = false, string $when = 'Always', ?string $whenMvel = null, string $type = TransitionType::EXTERNAL, ?string $callMethod = null, int $priority = 1)
    {
        $this->from = $from;
        $this->to = $to;
        $this->on = $on;
        $this->targetFinal = $targetFinal;
        $this->when = $when;
        $this->whenMvel = $whenMvel;
        $this->type = $type;
        $this->callMethod = $callMethod;
        $this->priority = $priority;
    }
    
    
    public function getWhen():string{
        switch($this->when){
            case 'Always':
                return Always::class;
            case 'Never':
                return Never::class;
        }
        return $this->when;
    }
}

