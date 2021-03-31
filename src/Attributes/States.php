<?php
namespace Pluf\Workflow\Attributes;

use Attribute;

/**
 * List of all possible states
 * 
 * NOTE: in PHP8 is not possible to use new in attributes so do not use in attributes
 */
#[Attribute]
class States
{
    /**
     * Creates new instance of the class
     */
    public function __construct(
        public array $value = []
    ) {}
}

