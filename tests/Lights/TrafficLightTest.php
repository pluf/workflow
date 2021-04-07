<?php
namespace Pluf\Tests\Lights;

use PHPUnit\Framework\TestCase;
use Pluf\Workflow\StateMachineBuilderFactory;

/**
 * To test traffic light state machine
 *
 * @author maso
 *        
 */
class TrafficLightTest extends TestCase
{

    /**
     * Creates new instance of state machine
     *
     * @test
     */
    public function createInstanceTest()
    {
        $builder = StateMachineBuilderFactory::create(TrafficLightFSM::class);
        $stateMachine = $builder->build('RED');
        $this->assertNotNull($stateMachine, 'The state machine is not created.');
    }

    /**
     * Generates all possiblet states
     *
     * @return string[][]
     */
    public function getAllPassibleStates()
    {
        return [
            [
                new TrafficLight('RED')
            ],
            [
                new TrafficLight('GREEN')
            ],
            [
                new TrafficLight('AMBER')
            ],
            [
                new TrafficLight('FLASHING_RED')
            ]
        ];
    }

    /**
     * Creates new instance of state machine with state
     *
     * @dataProvider getAllPassibleStates
     * @test
     */
    public function createInstanceWithStateTest(TrafficLight $trafficLight)
    {
        $builder = StateMachineBuilderFactory::create(TrafficLightFSM::class);
        $stateMachine = $builder->build($trafficLight->light)->start();
        $this->assertNotNull($stateMachine, 'The state machine is not created.');
        $this->assertEquals($trafficLight->light, $stateMachine->getCurrentState());
    }

    /**
     * Generates all possiblet transitionss
     *
     * @return string[][]
     */
    public function getAllPassibleTransition()
    {
        return [
            [
                new TrafficLight('RED'),
                'TIMER_EXPIRES',
                'GREEN'
            ],
            [
                new TrafficLight('GREEN'),
                'TIMER_EXPIRES',
                'AMBER'
            ],
            [
                new TrafficLight('AMBER'),
                'TIMER_EXPIRES',
                'RED'
            ],
            [
                new TrafficLight('RED'),
                'SYSTEM_ERROR',
                'FLASHING_RED'
            ],
            [
                new TrafficLight('GREEN'),
                'SYSTEM_ERROR',
                'FLASHING_RED'
            ],
            [
                new TrafficLight('AMBER'),
                'SYSTEM_ERROR',
                'FLASHING_RED'
            ],
            [
                new TrafficLight('FLASHING_RED'),
                'SYSTEM_RESTART',
                'RED'
            ]
        ];
    }

    /**
     * Creates new instance of state machine with state
     *
     * @dataProvider getAllPassibleTransition
     * @test
     */
    public function performeEventTest(TrafficLight $trafficLight, $event, $nextState)
    {
        $builder = StateMachineBuilderFactory::create(TrafficLightFSM::class);
        $stateMachine = $builder->build($trafficLight->light)
            ->start()
            ->fireEvent($event, [
            'trafficLight' => $trafficLight
        ]);
        $this->assertNotNull($stateMachine, 'The state machine is not created.');
        $this->assertEquals($trafficLight->light, $stateMachine->getCurrentState());
        $this->assertEquals($nextState, $stateMachine->getCurrentState());
    }
}

