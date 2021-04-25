<?php
namespace Pluf\Workflow\Exceptions;

use RuntimeException;

/**
 * Fail to comple a transaction
 *
 * @author maso
 *        
 */
class TransitionException extends RuntimeException
{

    public $from;

    public $to;

    public $event;

    public $context;

    public $actionName;

    /**
     * Creates new instance of the error
     *
     * @param mixed $message
     *            [optional]
     * @param mixed $code
     *            [optional]
     * @param mixed $previous
     *            [optional]
     */
    public function __construct($message = null, $code = null, $previous = null, $from = null, $to = null, $event = null, $context = null, $actionName = null)
    {
        parent::__construct($message, $code, $previous);
        $this->from = $from;
        $this->to = $to;
        $this->event = $event;
        $this->context = $context;
        $this->actionName = $actionName;
    }
}

