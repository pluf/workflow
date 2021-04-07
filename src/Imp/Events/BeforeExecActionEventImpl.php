<?php
namespace Pluf\Workflow\Imp\Events;

class BeforeExecActionEventImpl
{
    
    /**
     * Creates new instance
     *
     * @param mixed $position
     * @param mixed $actionTotalSize
     * @param mixed $actionContext
     */
    public function __construct(
        public $position, 
        public $actionTotalSize, 
        public $actionContext
        ){}
    
    /**
     * Creates new instance 
     * 
     * @param mixed $position
     * @param mixed $actionTotalSize
     * @param mixed $actionContext
     */
    public static function get($position, $actionTotalSize, $actionContext): BeforeExecActionEventImpl
    {
        return new BeforeExecActionEventImpl($position, $actionTotalSize, $actionContext);
    }
}



