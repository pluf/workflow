<?php
namespace Pluf\Workflow;

/**
 * @deprecated The PHP itself is untyped script language so this one is not the case anymore in PHP
 * @author maso
 *
 */
interface UntypedStateMachineBuilder extends StateMachineBuilder
{

    public function newUntypedStateMachine($initialStateId, StateMachineConfiguration $configuration, ...$extraParams);

    public function newAnyStateMachine($initialStateId, StateMachineConfiguration $configuration, ...$extraParams);
}

