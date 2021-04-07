<?php
namespace Pluf\Tests;

use PHPUnit\Framework\TestCase;
use Pluf\Tests\Atm\ATMStateMachineAnotation;
use Pluf\Workflow\Attributes\State;
use ReflectionClass;

class ApiTest extends TestCase
{

    /**
     * To load states from attributes
     *
     * @test
     */
    public function instanceStatesAttributes()
    {
        $reflector = new ReflectionClass(ATMStateMachineAnotation::class);
        $this->assertNotNull($reflector);

        $attributes = $reflector->getAttributes(State::class);
        $this->assertIsArray($attributes);
        $this->assertNotEmpty($attributes);
    }
}