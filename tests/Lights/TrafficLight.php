<?php
namespace Pluf\Tests\Lights;

class TrafficLight
{

    public ?string $light;

    public function __construct($light = 'RED')
    {
        $this->light = $light;
    }
}

