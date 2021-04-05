<?php
namespace Pluf\Workflow\Imp\Events;

class TransitionExceptionEventImpl
{
    
    public function __construct(
        public $xception,
        public $from,
        public $currentState,
        public $event,
        public $context,
        public $stateMachine
    ){ }
}

