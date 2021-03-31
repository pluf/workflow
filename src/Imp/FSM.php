<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\StateMachineData;
use Pluf\Workflow\Builder\ExternalTransitionBuilder;
use Pluf\Workflow\TransitionType;
use Pluf\Workflow\Builder\LocalTransitionBuilder;
use Pluf\Workflow\MutableTransition;
use Pluf\Workflow\MutableState;
use ArrayObject;
use Pluf\Workflow\StateMachine;
use Pluf\Workflow\ImmutableState;
use Pluf\Workflow\TransitionResult;
use Pluf\Workflow\ActionExecutionService;
use Pluf\Workflow\StateContext;

class FSM
{

    public static function newStateContext(StateMachine $stateMachine, StateMachineData $data, ?ImmutableState $sourceState, $event, $context, ?TransitionResult $result, ActionExecutionService $executor): StateContext
    {
        return new StateContextImpl($stateMachine, $data, $sourceState, $event, $context, $result, $executor);
    }

    public static function newTransition(): MutableTransition
    {
        return new TransitionImpl();
    }

    public static function newState($stateId)
    {
        return new StateImpl($stateId);
    }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> MutableLinkedState<T, S, E, C> newLinkedState(S stateId) {
    // return SquirrelProvider.getInstance().newInstance(new TypeReference<LinkedStateImpl<T, S, E, C>>() {},
    // new Class[] { Object.class }, new Object[] { stateId });
    // }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> MutableTimedState<T, S, E, C> newTimedState(S stateId) {
    // return SquirrelProvider.getInstance().newInstance(new TypeReference<TimedStateImpl<T, S, E, C>>() {},
    // new Class[] { Object.class }, new Object[] { stateId });
    // }
    public static function getState(ArrayObject $states, $stateId): MutableState
    {
        if ($states->offsetExists($stateId)) {
            $state = $states[$stateId];
        } else {
            $state = FSM::newState($stateId);
            $states[$stateId] = $state;
        }
        return $state;
    }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> DeferBoundActionBuilder<T, S, E, C> newDeferBoundActionBuilder(
    // List<DeferBoundActionInfo<T, S, E, C>> deferBoundActionInfoList, ExecutionContext executionContext
    // ) {
    // return SquirrelProvider.getInstance().newInstance( new TypeReference<DeferBoundActionBuilderImpl<T, S, E, C>>(){},
    // new Class[]{List.class, ExecutionContext.class}, new Object[]{deferBoundActionInfoList, executionContext} );
    // }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> MultiTransitionBuilder<T, S, E, C> newMultiTransitionBuilder(
    // Map<S, MutableState<T, S, E, C>> states, TransitionType transitionType, int priority, ExecutionContext executionContext
    // ) {
    // return SquirrelProvider.getInstance().newInstance(new TypeReference<MultiTransitionBuilderImpl<T, S, E, C>>() {},
    // new Class[] { Map.class, TransitionType.class, int.class, ExecutionContext.class },
    // new Object[] { states, transitionType, priority, executionContext });
    // }
    public static function newExternalTransitionBuilder($states, int $priority, $executionContext): ExternalTransitionBuilder
    {
        return new TransitionBuilderImpl($states, TransitionType::EXTERNAL, $priority, $executionContext);
    }

    public static function newLocalTransitionBuilder($states, int $priority, $executionContext): LocalTransitionBuilder
    {
        return new TransitionBuilderImpl($states, TransitionType::LOCAL, $priority, $executionContext);
    }

    public static function newInternalTransitionBuilder($states, int $priority, $executionContext): LocalTransitionBuilder
    {
        return new TransitionBuilderImpl($states, TransitionType::INTERNAL, $priority, $executionContext);
    }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> EntryExitActionBuilder<T, S, E, C> newEntryExitActionBuilder(
    // MutableState<T, S, E, C> state, boolean isEntryAction, ExecutionContext executionContext) {
    // return SquirrelProvider.getInstance().newInstance(new TypeReference<EntryExitActionBuilderImpl<T, S, E, C>>() {},
    // new Class[] { MutableState.class, boolean.class, ExecutionContext.class},
    // new Object[] { state, isEntryAction, executionContext});
    // }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> MethodCallActionImpl<T, S, E, C> newMethodCallAction(
    // Method method, int weight, ExecutionContext executionContext) {
    // return SquirrelProvider.getInstance().newInstance(
    // new TypeReference<MethodCallActionImpl<T, S, E, C>>() {},
    // new Class[] { Method.class, int.class, ExecutionContext.class },
    // new Object[] { method, weight, executionContext } );
    // }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> MethodCallActionProxyImpl<T, S, E, C> newMethodCallActionProxy(
    // String methodName, ExecutionContext executionContext) {
    // return SquirrelProvider.getInstance().newInstance(new TypeReference<MethodCallActionProxyImpl<T, S, E, C>>() {},
    // new Class[] { String.class, ExecutionContext.class }, new Object[] { methodName, executionContext });
    // }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> Actions<T, S, E, C> newActions() {
    // return SquirrelProvider.getInstance().newInstance(new TypeReference<Actions<T, S, E, C>>() {});
    // }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> TransitionResult<T, S, E, C> newResult(
    // boolean accepted, ImmutableState<T, S, E, C> targetState, TransitionResult<T, S, E, C> parent) {
    // return SquirrelProvider.getInstance().newInstance(new TypeReference<TransitionResult<T, S, E, C>>() {}).
    // setAccepted(accepted).setTargetState(targetState).setParent(parent);
    // }

    // static <C> Condition<C> newMvelCondition(String expression, MvelScriptManager scriptManager) {
    // return SquirrelProvider.getInstance().newInstance(new TypeReference<MvelConditionImpl<C>>() {},
    // new Class[]{String.class, MvelScriptManager.class}, new Object[]{expression, scriptManager});
    // }

    // static <T extends StateMachine<T, S, E, C>, S, E, C> Action<T, S, E, C> newMvelAction(
    // String expression, ExecutionContext executionContext) {
    // return SquirrelProvider.getInstance().newInstance(new TypeReference<MvelActionImpl<T, S, E, C>>() {},
    // new Class[]{String.class, ExecutionContext.class}, new Object[]{expression, executionContext});
    // }
    public static function newStateMachineData($datastates): StateMachineData
    {
        return new StateMachineDataImpl($datastates);
    }
}

