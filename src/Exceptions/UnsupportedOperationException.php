<?php
namespace Pluf\Workflow\Exceptions;

use RuntimeException;

class UnsupportedOperationException extends RuntimeException
{

    /**
     *
     * @param mixed $message
     *            [optional]
     * @param mixed $code
     *            [optional]
     * @param mixed $previous
     *            [optional]
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

