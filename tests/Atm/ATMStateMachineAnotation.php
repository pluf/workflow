<?php
namespace Pluf\Tests\Atm;

use Pluf\Workflow\Attributes\State;
use Pluf\Workflow\Attributes\Transit;


#[
State(name: 'Idle'),
State(name: 'Loading'),
State(name: 'Disconnected'),
State(name: 'OutOfService'),
State(name: 'InService'),

Transit(from: 'Idle', to: 'Loading', on: 'Connected'),
Transit(from: 'Loading', to: 'Disconnected', on: 'ConnectionClosed'),
Transit(from: 'Loading', to: 'InService', on: 'LoadSuccess'),
Transit(from: 'Loading', to: 'OutOfService', on: 'LoadFail'),
Transit(from: 'OutOfService', to: 'Disconnected', on: 'ConnectionLost'),
Transit(from: 'OutOfService', to: 'InService', on: 'Startup'),
Transit(from: 'InService', to: 'OutOfService', on: 'Shutdown'),
Transit(from: 'InService', to: 'Disconnected', on: 'ConnectionLost'),
Transit(from: 'Disconnected', to: 'InService', on: 'ConnectionRestored')
]
class ATMStateMachineAnotation extends ATMStateMachine
{
}

