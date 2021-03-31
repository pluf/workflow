<?php
namespace Pluf\Workflow\Builder;

interface DeferBoundActionBuilder
{

    public function fromAny(): DeferBoundActionFrom;

    public function from($from): DeferBoundActionFrom;
}

