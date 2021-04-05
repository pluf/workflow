<?php
namespace Pluf\Workflow\Imp;

use RuntimeException;

trait AssertTrait
{

    public function assertNotEmpty($value, $message = "Value must not be null", array $params = [])
    {
        if (empty($value)) {
            throw new RuntimeException($message);
        }
    }

    public function assertEmpty($value, $message = "Value must be empty", array $params = [])
    {
        if (! empty($value)) {
            throw new RuntimeException($message);
        }
    }

    public function assertTrue($value, $message = "Value must be true", array $params = [])
    {
        if (! $value) {
            throw new RuntimeException($message);
        }
    }
}

