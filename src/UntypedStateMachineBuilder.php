<?php
namespace Pluf\Workflow;

interface UntypedStateMachineBuilder extends StateMachineBuilder
{

    public function newUntypedStateMachine($initialStateId, StateMachineConfiguration $configuration, ...$extraParams);

    public function newAnyStateMachine($initialStateId, StateMachineConfiguration $configuration, ...$extraParams);
}

