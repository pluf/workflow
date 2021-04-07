<?php
namespace Pluf\Tests\Atm;

use PHPUnit\Framework\TestCase;
use Pluf\Workflow\StateMachineBuilderFactory;
use Pluf\Workflow\StateMachine;

class ATMStateMachineTest extends TestCase
{
    
    private ?StateMachine $stateMachine = null;
    
    /**
     * @before
     */
    public function setUpTest() {
        $builder = StateMachineBuilderFactory::create(ATMStateMachine::class);
        $builder->externalTransition()
            ->from('Idle')
            ->to('Loading')
            ->on('Connected');
        $builder->externalTransition()
            ->from('Loading')
            ->to('Disconnected')
            ->on("ConnectionClosed");
        $builder->externalTransition()
            ->from('Loading')
            ->to('InService')
            ->on("LoadSuccess");
        $builder->externalTransition()
            ->from('Loading')
            ->to('OutOfService')
            ->on("LoadFail");
        $builder->externalTransition()
            ->from('OutOfService')
            ->to('Disconnected')
            ->on("ConnectionLost");
        $builder->externalTransition()
            ->from('OutOfService')
            ->to('InService')
            ->on("Startup");
        $builder->externalTransition()
            ->from('InService')
            ->to('OutOfService')
            ->on("Shutdown");
        $builder->externalTransition()
            ->from('InService')
            ->to('Disconnected')
            ->on("ConnectionLost");
        $builder->externalTransition()
            ->from('Disconnected')
            ->to('InService')
            ->on("ConnectionRestored");
        
        $this->stateMachine = $builder->build('Idle');
        $this->assertNotNull($this->stateMachine, 'The state machine is not created.');
    }
    
    /**
     * @after
     */
    public function teardownTest() {
        if($this->stateMachine!=null && $this->stateMachine->getStatus()!='TERMINATED') {
            $this->stateMachine->terminate(null);
        }
    }
    
    /**
     * @test
     */
    public function testIdelToInService() {
        $this->stateMachine->start();
        $imple = $this->stateMachine->getImplementation();
        
        $this->assertEquals("entryIdle.", $imple->consumeLog());
        $this->assertEquals('Idle',      $this->stateMachine->getCurrentState());
        
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

