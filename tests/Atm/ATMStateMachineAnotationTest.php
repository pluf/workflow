<?php
namespace Pluf\Tests\Atm;

use PHPUnit\Framework\TestCase;
use Pluf\Workflow\StateMachineBuilderFactory;
use Pluf\Workflow\StateMachine;

class ATMStateMachineAnotationTest extends TestCase
{

    private ?StateMachine $stateMachine = null;

    /**
     *
     * @before
     */
    public function setUpTest()
    {
        $builder = StateMachineBuilderFactory::create(ATMStateMachineAnotation::class);
        $this->stateMachine = $builder->build('Idle');
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

        $this->assertEquals("entryIdle.", $imple->consumeLog());
        $this->assertEquals('Idle', $this->stateMachine->getCurrentState());

        $this->stateMachine->fireEvent("Connected");
        $this->assertEquals("exitIdle.transitFromIdleToLoadingOnConnected.entryLoading.", $imple->consumeLog());
        $this->assertEquals('Loading', $this->stateMachine->getCurrentState());

        $this->stateMachine->fireEvent("LoadSuccess");
        $this->assertEquals("exitLoading.transitFromLoadingToInServiceOnLoadSuccess.entryInService.", $imple->consumeLog());
        $this->assertEquals('InService', $this->stateMachine->getCurrentState());

        $this->stateMachine->fireEvent("Shutdown");
        $this->assertEquals("exitInService.transitFromInServiceToOutOfServiceOnShutdown.entryOutOfService.", $imple->consumeLog());
        $this->assertEquals('OutOfService', $this->stateMachine->getCurrentState());

        $this->stateMachine->fireEvent("ConnectionLost");
        $this->assertEquals("exitOutOfService.transitFromOutOfServiceToDisconnectedOnConnectionLost.entryDisconnected.", $imple->consumeLog());
        $this->assertEquals('Disconnected', $this->stateMachine->getCurrentState());

        $this->stateMachine->fireEvent("ConnectionRestored");
        $this->assertEquals("exitDisconnected.transitFromDisconnectedToInServiceOnConnectionRestored.entryInService.", $imple->consumeLog());
        $this->assertEquals('InService', $this->stateMachine->getCurrentState());
    }
}

