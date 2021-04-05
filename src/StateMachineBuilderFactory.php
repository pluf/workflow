<?php
namespace Pluf\Workflow;

use Pluf\Workflow\Imp\StateMachineBuilderImpl;
use Pluf\Di\Container;

/**
 * State machine builder factory to create the state machine builder.
 * Here we use {@link SquirrelProvider} to create the
 * builder, so user can register different implementation class of {@link StateMachineBuilder} to instantiate different
 * builder. And also user can register different type of post processor to post process created builder.
 *
 * @author Henry.He
 *        
 */
class StateMachineBuilderFactory
{

    /**
     * Creates new instance of state machine builder
     *
     * @param string $stateMachineClazz
     * @param string $stateClass
     * @param string $eventClass
     * @param string $contextClazz
     * @param array $extraConstParamTypes
     * @param mixed $container
     * @return UntypedStateMachineBuilder
     */
    public static function create(?string $stateMachineClazz = null, 
        ?string $stateClass = 'string', 
        ?string $eventClass = 'string', 
        ?string $contextClazz = null, 
        array $extraConstParamTypes = [], 
        ?Container $container = null): UntypedStateMachineBuilder
    {
        $builder = new StateMachineBuilderImpl();
        if(!isset($container)){
            $container = new Container();
        }
        return $builder->setStateMachinClass($stateMachineClazz)
            ->setContainer($container)
            ->setStateType($stateClass)
            ->setEventType($eventClass)
            ->setContextType($contextClazz)
            ->setScanAnnotations(true);
    }
}