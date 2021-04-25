<?php
namespace Pluf\Tests\Beep;

use PHPUnit\Framework\TestCase;
use Pluf\Workflow\StateMachineBuilderFactory;
use Pluf\Workflow\StateMachine;

class BeeperTest extends TestCase
{

    private ?StateMachine $stateMachine = null;

    /**
     *
     * @before
     */
    public function setUpTest()
    {
        $builder = StateMachineBuilderFactory::create(Beeper::class);
        $this->stateMachine = $builder->build('ready');
        $this->assertNotNull($this->stateMachine, 'The state machine is not created.');
    }

    /**
     *
     * @after
     */
    public function teardownTest()
    {
        if ($this->stateMachine != null && $this->stateMachine->getStatus() != 'TERMINATED') {
            $this->stateMachine->terminate(null);
        }
    }

    /**
     *
     * @test
     */
    public function testIdelToInService()
    {
        $this->stateMachine->start();
        $imple = $this->stateMachine->getImplementation();

        $this->assertEquals(0, $imple->count);
        $this->assertFalse($this->stateMachine->canAccept("trigger"));

        $this->stateMachine->fireEvent("trigger", [
            'count' => 1
        ]);
        $this->assertEquals(1, $imple->count);
        $this->assertEquals('ready', $this->stateMachine->getCurrentState());
        
        
        $this->stateMachine->fireEvent("trigger", [
            'count' => 1
        ]);
        $this->assertEquals(2, $imple->count);
        $this->assertEquals('ready', $this->stateMachine->getCurrentState());
    }
}

