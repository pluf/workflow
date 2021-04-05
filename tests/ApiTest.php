<?php
namespace Pluf\Tests;

use PHPUnit\Framework\TestCase;
use Pluf\Tests\Atm\ATMStateMachineAnotation;
use Pluf\Workflow\Attributes\State;
use Pluf\Workflow\Attributes\States;
use ReflectionClass;

class ApiTest extends TestCase
{

    /**
     * Can create new instance
     *
     * @test
     */
    public function instanceStatesProg()
    {
        // Machine
        $wm = new States();
        $this->assertNotNull($wm);
        $wm = new States([
            new State(name: 'a', finalState: false),
            new State(name: 'b', finalState: false),
            new State(name: 'c', finalState: true)
        ]);
        $wm = new States();
    }

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