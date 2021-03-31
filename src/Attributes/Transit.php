<?php
namespace Pluf\Workflow\Attributes;

use Pluf\Workflow\Condition;

class Transit
{
    
    public ?string $from;
    
    public ?string $to;
    
    public ?string $on;
    
    public bool $targetFinal = false;
    
    public ?Condition $when = null; // Conditions::Always;
    
    public ?string $whenMvel = null;
//     TransitionType type() default TransitionType.EXTERNAL;
    public ?string $callMethod = null;
    public int $priority = 0; // TransitionPriority.NORMAL;
}

