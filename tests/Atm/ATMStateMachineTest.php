<?php
namespace Pluf\Tests\Atm;

use PHPUnit\Framework\TestCase;
use Pluf\Workflow\StateMachineBuilderFactory;
use Pluf\Workflow\StateMachine;

class ATMStateMachineTest extends TestCase
{
    
    /**
     * @afterClass
     */
    public static function afterTest() {
        ConverterProvider.INSTANCE.clearRegistry();
        SquirrelPostProcessorProvider.getInstance().clearRegistry();
    }
    
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
        
        $this->stateMachine = $builder->newStateMachine('Idle');
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
        $this->assertEquals("entryIdle", $this->stateMachine->consumeLog(), is(equalTo()));
        $this->assertEquals('Idle',      $this->stateMachine->getCurrentState(), is(equalTo(ATMState)));
        
        $this->stateMachine->fire("Connected");
        $this->assertEquals("exitIdle.transitFromIdleToLoadingOnConnected.entryLoading", $this->stateMachine->consumeLog());
        $this->assertEquals('Loading', $this->stateMachine->getCurrentState());
        
        $this->stateMachine->fire("LoadSuccess");
        $this->assertEquals("exitLoading.transitFromLoadingToInServiceOnLoadSuccess.entryInService", $this->stateMachine->consumeLog());
        $this->assertEquals('InService', $this->stateMachine->getCurrentState());
        
        $this->stateMachine->fire("Shutdown");
        $this->assertEquals("exitInService.transitFromInServiceToOutOfServiceOnShutdown.entryOutOfService", $this->stateMachine->consumeLog());
        $this->assertEquals('OutOfService', $this->stateMachine->getCurrentState());
        
        $this->stateMachine->fire("ConnectionLost");
        $this->assertEquals("exitOutOfService.transitFromOutOfServiceToDisconnectedOnConnectionLost.entryDisconnected", $this->stateMachine->consumeLog());
        $this->assertEquals('Disconnected', $this->stateMachine->getCurrentState());
        
        $this->stateMachine->fire("ConnectionRestored");
        $this->assertEquals("exitDisconnected.transitFromDisconnectedToInServiceOnConnectionRestored.entryInService", $this->stateMachine->consumeLog());
        $this->assertEquals('InService', $this->stateMachine->getCurrentState());
    }
    
//     /**
//      * @Test
//      */
//     public function exportAndImportATMStateMachine() {
//         SCXMLVisitor visitor = SquirrelProvider.getInstance().newInstance(SCXMLVisitor.class);
//         stateMachine.accept(visitor);
//         // visitor.convertSCXMLFile("ATMStateMachine", true);
//         String xmlDef = visitor.getScxml(false);
//         UntypedStateMachineBuilder builder = new UntypedStateMachineImporter().importDefinition(xmlDef);
        
//         ATMStateMachine stateMachine = builder.newAnyStateMachine(ATMState.Idle);
//         stateMachine.start();
//         assertThat(stateMachine.consumeLog(), is(equalTo("entryIdle")));
//         assertThat(stateMachine.getCurrentState(), is(equalTo(ATMState.Idle)));
        
//         stateMachine.fire("Connected");
//         assertThat(stateMachine.consumeLog(), is(equalTo("exitIdle.transitFromIdleToLoadingOnConnected.entryLoading")));
//         assertThat(stateMachine.getCurrentState(), is(equalTo(ATMState.Loading)));
//     }
}

