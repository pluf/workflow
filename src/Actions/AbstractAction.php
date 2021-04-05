<?php
namespace Pluf\Workflow\Actions;

use Pluf\Workflow\Imp\AssertTrait;

abstract class AbstractAction
{

    use AssertTrait;

    public int $weight;

    public string $name;

    public function __construct(string $name, int $weight = 1)
    {
        $this->name = $name;
        $this->weight = $weight;
    }

    public function getWeight(): string
    {
        return $this->weight;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

