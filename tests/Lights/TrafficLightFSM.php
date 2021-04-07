<?php
namespace Pluf\Tests\Lights;

use Pluf\Workflow\Attributes\State;
use Pluf\Workflow\Attributes\Transit;


#[State(name: 'RED', initialState: true)]
#[State(name: 'GREEN')]
#[State(name: 'AMBER')]
#[State(name: 'FLASHING_RED')]
#[Transit(from: 'RED', to: 'FLASHING_RED'  , on: 'SYSTEM_ERROR', callMethod: 'updateTrafficLight')]
#[Transit(from: 'GREEN', to: 'FLASHING_RED', on: 'SYSTEM_ERROR', callMethod: 'updateTrafficLight')]
#[Transit(from: 'AMBER', to: 'FLASHING_RED', on: 'SYSTEM_ERROR', callMethod: 'updateTrafficLight')]
#[Transit(from: 'FLASHING_RED', to: 'RED', on: 'SYSTEM_RESTART', callMethod: 'updateTrafficLight')]
#[Transit(from: 'RED', to: 'GREEN', on: 'TIMER_EXPIRES', callMethod: 'updateTrafficLight')]
#[Transit(from: 'GREEN', to: 'AMBER', on: 'TIMER_EXPIRES', callMethod: 'updateTrafficLight')]
#[Transit(from: 'AMBER', to: 'RED', on: 'TIMER_EXPIRES', callMethod: 'updateTrafficLight')]
class TrafficLightFSM
{

    public function updateTrafficLight($to, ?TrafficLight $trafficLight = null)
    {
        if (isset($trafficLight)) {
            $trafficLight->light = $to;
        }
    }
}

