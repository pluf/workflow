<?php
namespace Pluf\Workflow\Imp\Events;

class ExecActionExceptionEventImpl
{
    /**
     * Creates new instance
     *
     * @param mixed $position
     * @param mixed $actionTotalSize
     * @param mixed $actionContext
     */
    public function __construct(
        public $exception,
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
        public static function get($e, $position, $actionTotalSize, $actionContext): ExecActionExceptionEventImpl
        {
            return new ExecActionExceptionEventImpl($e, $position, $actionTotalSize, $actionContext);
        }
}


