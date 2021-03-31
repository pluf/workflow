<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\HistoryType;
use Pluf\Workflow\MutableState;
use Pluf\Workflow\StateMachineConfiguration;
use Pluf\Workflow\UntypedStateMachineBuilder;
use Pluf\Workflow\StateMachineBuilder;
use Pluf\Workflow\Builder\DeferBoundActionBuilder;
use Pluf\Workflow\Builder\EntryExitActionBuilder;
use Pluf\Workflow\Builder\ExternalTransitionBuilder;
use Pluf\Workflow\Builder\InternalTransitionBuilder;
use Pluf\Workflow\Builder\LocalTransitionBuilder;
use Pluf\Workflow\Builder\MultiTransitionBuilder;
use Pluf\Workflow\IllegalStateException;
use Pluf\Workflow\Attributes\State;
use Pluf\Workflow\Attributes\Transit;
use Pluf\Workflow\StateMachine;
use Pluf\Workflow\Actions\FinalStateGuardAction;
use Pluf\Workflow\IllegalArgumentException;
use ReflectionClass;
use ArrayObject;
use Pluf\Workflow\Action;
use Pluf\Workflow\MutableTransition;

class StateMachineBuilderImpl implements UntypedStateMachineBuilder, StateMachineBuilder
{

    // static {
    // DuplicateChecker.checkDuplicate(StateMachineBuilder.class);
    // }

    // private static final Logger logger = LoggerFactory.getLogger(StateMachineBuilderImpl.class);
    private $container = null;

    public ArrayObject $states;

    private ?string $stateMachineImplClazz;

    private ?string $stateType;

    private ?string $eventType;

    private ?string $contextType;

    private bool $prepared = false;

    // private final Constructor<? extends T> constructor;
    private ?string $postConstructMethod = null;

    protected $stateConverter;

    protected $eventConverter;

    private array $methodCallParamTypes = [];

    private array $stateAliasToDescription = [];

    private $scriptManager;

    // MvelScriptManager
    private $startEvent;

    private $finishEvent;

    private $terminateEvent;

    private $executionContext;

    // ExecutionContext
    private array $deferBoundActionInfoList = [];

    private bool $scanAnnotations = true;

    private array $extraParamTypes = [];

    private StateMachineConfiguration $defaultConfiguration;

    public function __construct()
    {
        $this->states = new ArrayObject();
    }

    private function checkState()
    {
        if ($this->prepared) {
            throw new IllegalStateException("The state machine builder has been freezed and cannot be changed anymore.");
        }
    }

    private function prepare(): void
    {
        if ($this->prepared) {
            return;
        }

        if ($this->scanAnnotations) {
            // 1. install all the declare states, states must be installed before installing transition and extension methods
            $this->walkThroughStateMachineClassForState();
            // 2. install all the declare transitions
            $this->walkThroughStateMachineClassForTransition();
            // 2.5 install all the defer bound actions
            $this->installDeferBoundActions();
        }
        // 3. install all the extension method call when state machine builder freeze
        $this->installExtensionMethods();
        // 4. prioritize transitions
        $this->prioritizeTransitions();
        // 5. install final state actions
        $this->installFinalStateActions();
        // 6. verify correctness of state machine
        $this->verifyStateMachineDefinition();
        // 7. proxy untyped states
        $this->proxyUntypedStates();
        $this->prepared = true;
    }

    private function walkThroughStateMachineClassForState(): void
    {
        $stack = [];
        array_push($stack, new ReflectionClass($this->stateMachineImplClazz));
        while (! empty($stack)) {
            // Adds states
            $k = array_pop($stack);
            $states = $k->getAttributes(State::class);
            foreach ($states as $state) {
                $this->buildDeclareState($state->newInstance());
            }

            // push supper classess for next itteration
            forEach ($k->getInterfaces() as $i) {
                if ($this->isStateMachineInterface($i)) {
                    array_push($stack, $i);
                }
            }
            if ($this->isStateMachineType($k->getParentClass())) {
                array_push($stack, $k->getParentClass());
            }
        }
    }

    private function walkThroughStateMachineClassForTransition(): void
    {
        $stack = [];
        array_push($stack, new ReflectionClass($this->stateMachineImplClazz));
        while (! empty($stack)) {
            // Adds states
            $k = array_pop($stack);
            $transitions = $k->getAttributes(Transit::class);
            foreach ($transitions as $transit) {
                $this->buildDeclareTransition($transit->newInstance());
            }

            // push supper classess for next itteration
            forEach ($k->getInterfaces() as $i) {
                if ($this->isStateMachineInterface($i)) {
                    array_push($stack, $i);
                }
            }
            if ($this->isStateMachineType($k->getParentClass())) {
                array_push($stack, $k->getParentClass());
            }
        }
    }

    private function buildDeclareState(State $state): void
    {
        // Preconditions.checkState(stateConverter!=null, "Do not register state converter");
        $stateId = $this->stateConverter->convertFromString($state->name);
        // Preconditions.checkNotNull(stateId, "Cannot convert state of name \""+state.name()+"\".");
        $newState = $this->defineState($stateId);
        $newState->setCompositeType($state->compositeType);
        if (! $newState->isParallelState()) {
            $newState->setHistoryType($state->historyType);
        }
        $newState->setFinal($state->finalState);

        if (! empty($state->parent)) {
            $parentStateId = $this->stateConverter->convertFromString($this->parseStateId($state->parent));
            $parentState = $this->defineState($parentStateId);
            $newState->setParentState($parentState);
            $parentState->addChildState($newState);
            if (! $parentState->isParallelState() && $state->initialState) {
                $parentState->setInitialState($newState);
            }
        }

        if (! empty($state->entryCallMethod)) {
            $methodCallAction = FSM::newMethodCallActionProxy($state->entryCallMethod, $this->executionContext);
            $this->onEntry($stateId)->perform($methodCallAction);
        }

        if (! empty($state->exitCallMethod)) {
            $methodCallAction = FSM::newMethodCallActionProxy($state->exitCallMetho, $this->executionContext);
            $this->onExit($stateId)->perform($methodCallAction);
        }
        $this->rememberStateAlias($state);
    }

    private function installDeferBoundActions(): void
    {
        // if(empty($this->deferBoundActionInfoList)){
        // return;
        // }
        foreach ($this->deferBoundActionInfoList as $deferBoundActionInfo) {
            $this->installDeferBoundAction($deferBoundActionInfo);
        }
    }

    private function installDeferBoundAction(DeferBoundActionInfo $deferBoundActionInfo)
    {
        foreach ($this->states as $mutableState) {
            if (! $deferBoundActionInfo->isFromStateMatch($mutableState->getStateId())) {
                continue;
            }
            $trs = $mutableState->getAllTransitions();
            foreach ($trs as $transition) {
                if ($deferBoundActionInfo->isToStateMatch($transition->getTargetState()
                    ->getStateId()) && $deferBoundActionInfo->isEventStateMatch($transition->getEvent())) {
                    $transition->addActions($deferBoundActionInfo->getActions());
                }
            }
        }
    }

    private function installExtensionMethods(): void
    {
        foreach ($this->states as $state) {
            // Ignore all the transition start from a final state
            if ($state->isFinalState()) {
                continue;
            }

            // state exit extension method
            $exitMethodCallCandidates = $this->getEntryExitStateMethodNames($state, false);
            foreach ($exitMethodCallCandidates as $exitMethodCallCandidate) {
                $this->addStateEntryExitMethodCallAction($exitMethodCallCandidate, $this->methodCallParamTypes, $state, false);
            }

            // transition extension methods
            $trx = $state->getAllTransitions();
            foreach ($trx as $transition) {
                $transitionMethodCallCandidates = $this->getTransitionMethodNames($transition);
                foreach ($transitionMethodCallCandidates as $transitionMethodCallCandidate) {
                    $this->addTransitionMethodCallAction($transitionMethodCallCandidate, $this->methodCallParamTypes, $transition);
                }
            }

            // state entry extension method
            $entryMethodCallCandidates = $this->getEntryExitStateMethodNames($state, true);
            foreach ($entryMethodCallCandidates as $entryMethodCallCandidate) {
                $this->addStateEntryExitMethodCallAction($entryMethodCallCandidate, $this->methodCallParamTypes, $state, true);
            }
        }
    }

    private function addTransitionMethodCallAction(string $methodName, $parameterTypes, MutableTransition $mutableTransition): void
    {
        $method = $this->findMethodCallActionInternal($this->stateMachineImplClazz, $methodName, $parameterTypes);
        if ($method != null) {
            $methodCallAction = FSM::newMethodCallAction($method, Action::EXTENSION_WEIGHT, $this->executionContext);
            $mutableTransition->addAction($methodCallAction);
        }
    }

    private function addStateEntryExitMethodCallAction(string $methodName, $parameterTypes, MutableState $mutableState, bool $isEntryAction): void
    {
        $method = $this->findMethodCallActionInternal($this->stateMachineImplClazz, $methodName, $parameterTypes);
        if ($method != null) {
            $weight = Action::EXTENSION_WEIGHT;
            if ($methodName->startsWith("before")) {
                $weight = Action::BEFORE_WEIGHT;
            } else if ($methodName->startsWith("after")) {
                $weight = Action::AFTER_WEIGHT;
            }
            $methodCallAction = FSM::newMethodCallAction($method, $weight, $this->executionContext);
            if ($isEntryAction) {
                $mutableState->addEntryAction($methodCallAction);
            } else {
                $mutableState->addExitAction($methodCallAction);
            }
        }
    }

    private function getEntryExitStateMethodNames($state, bool $isEntry): array
    {
        $prefix = ($isEntry ? "entry" : "exit");
        $postfix = ($isEntry ? "EntryAny" : "ExitAny");
        // XXX: capitilize state name
        return [
            "before" . $postfix,
            $prefix . (($this->stateConverter != null && ! $state->isFinalState()) ? $this->stateConverter->convertToString($state->getStateId()) : $state),
            "after" . $postfix
        ];
    }

    private function getTransitionMethodNames($transition): array
    {
        $fromState = $transition->getSourceState();
        $toState = $transition->getTargetState();
        $event = $transition->getEvent();
        $fromStateName = $this->stateConverter != null ? $this->stateConverter . convertToString($fromState->getStateId()) : $fromState;
        $toStateName = ($this->stateConverter != null && ! $toState->isFinalState()) ? $this->stateConverter->convertToString($toState->getStateId()) : $toState;
        $eventName = $this->eventConverter != null ? $this->eventConverter->convertToString($event) : $event;
        $conditionName = $transition->getCondition()->name();

        // XXX: maso, 2020: capitilize names
        return [
            "transitFrom" . $fromStateName . "To" . $toStateName . "On" . $eventName . "When" . $conditionName,
            "transitFrom" . $fromStateName . "To" . $toStateName . "On" . $eventName,
            "transitFromAnyTo" . $toStateName . "On" . $eventName,
            "transitFrom" . $fromStateName . "ToAnyOn" . $eventName,
            "transitFrom" . $fromStateName . "To" . $toStateName,
            "on" . $eventName
        ];
    }

    private function prioritizeTransitions(): void
    {
        foreach ($this->states as $state) {
            if ($state->isFinalState()) {
                continue;
            }
            $state->prioritizeTransitions();
        }
    }

    private function installFinalStateActions(): void
    {
        foreach ($this->states as $state) {
            if (! $state->isFinalState()) {
                continue;
            }
            // defensive code: final state cannot be exited anymore
            $state->addExitAction(new FinalStateGuardAction());
        }
    }

    private function verifyStateMachineDefinition(): void
    {
        foreach ($this->states as $state) {
            $state->verify();
        }
    }

    private function proxyUntypedStates(): void
    {
        // NOTE: untyped FSM is not the case in PHP
        // if(UntypedStateMachine.class.isAssignableFrom(stateMachineImplClazz)) {
        // $untypedStates = [];
        // foreach($this->states as $state) {
        // UntypedMutableState untypedState = (UntypedMutableState) Proxy.newProxyInstance(
        // UntypedMutableState.class.getClassLoader(),
        // new Class[]{UntypedMutableState.class, UntypedImmutableState.class},
        // new InvocationHandler() {
        // @Override
        // public Object invoke(Object proxy, Method method, Object[] args)
        // throws Throwable {
        // if (method.getName().equals("getStateId")) {
        // return state.getStateId();
        // } else if(method.getName().equals("getThis")) {
        // return state.getThis();
        // } else if(method.getName().equals("equals")) {
        // return state.equals(args[0]);
        // } else if(method.getName().equals("hashCode")) {
        // return state.hashCode();
        // }
        // return method.invoke(state, args);
        // }
        // });
        // untypedStates.put(state.getStateId(), MutableState.class.cast(untypedState));
        // }
        // states.clear();
        // states.putAll(untypedStates);
        // }
    }

    static function findMethodCallActionInternal($target, string $methodName, $parameterTypes)
    {
        return self::searchMethod($target, AbstractStateMachine::class, $methodName, $parameterTypes);
    }

    private static function searchMethod(string $targetClass, string $superClass, string $methodName, $parameterTypes)
    {
        // if(superClass.isAssignableFrom(targetClass)) {
        // $clazz = targetClass;
        // while(!superClass.equals(clazz)) {
        // try {
        // return clazz.getDeclaredMethod(methodName, parameterTypes);
        // } catch (NoSuchMethodException e) {
        // clazz = clazz.getSuperclass();
        // }
        // }
        // }
        return null;
    }

    public function setStateMachinClass($stateMachineClass): self
    {
        $this->checkState();
        $this->stateMachineImplClazz = $stateMachineClass;
        return $this;
    }

    public function setContainer($container): self
    {
        $this->checkState();
        $this->container = $container;
        return $this;
    }

    public function setStateType($stateType): self
    {
        $this->checkState();
        $this->stateType = $stateType;
        return $this;
    }

    public function setEventType($eventType): self
    {
        $this->checkState();
        $this->eventType = $eventType;
        return $this;
    }

    public function setContextType($contextType): self
    {
        $this->checkState();
        $this->contextType = $contextType;
        return $this;
    }

    private function isInstantiableType(?string $type = null): bool
    {
        if (! isset($type)) {
            return false;
        }
        $reflection = new \ReflectionClass($type);
        return $reflection->isInstantiable();
    }

    private function isStateMachineType(string $stateMachineClazz): bool
    {
        return is_subclass_of($stateMachineClazz, AbstractStateMachine::class, true);
        // stateMachineClazz!= null && AbstractStateMachine.class != stateMachineClazz &&
        // AbstractStateMachine.class.isAssignableFrom(stateMachineClazz);
    }

    private function isStateMachineInterface(string $stateMachineClazz): bool
    {
        return is_subclass_of($stateMachineClazz, StateMachine::class, true);
        // return stateMachineClazz!= null && stateMachineClazz.isInterface() &&
        // StateMachine.class.isAssignableFrom(stateMachineClazz);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\UntypedStateMachineBuilder::newAnyStateMachine()
     */
    public function newAnyStateMachine($initialStateId, StateMachineConfiguration $configuration, ...$extraParams)
    {}

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::externalTransition()
     */
    public function externalTransition(int $priority = 1): ExternalTransitionBuilder
    {
        $this->checkState();
        return FSM::newExternalTransitionBuilder($this->states, $priority, $this->executionContext);
    }

    public function defineTerminateEvent($terminateEvent): void
    {}

    public function defineStartEvent($startEvent): void
    {}

    public function onExit($stateId): EntryExitActionBuilder
    {}

    public function externalTransitions(int $priority = 0): MultiTransitionBuilder
    {}

    public function defineLinkedState($stateId, $linkedStateMachineBuilder, $initialLinkedState, $extraParams): MutableState
    {}

    public function defineState($stateId): MutableState
    {}

    public function transitions(int $priority = 0): MultiTransitionBuilder
    {}

    public function defineFinishEvent($finishEvent): void
    {}

    public function transition(int $priority = 0): ExternalTransitionBuilder
    {}

    public function defineSequentialStatesOn($parentStateId, $childStateIds, ?HistoryType $historyType = null): void
    {}

    public function setStateMachineConfiguration(StateMachineConfiguration $configure): void
    {}

    public function newUntypedStateMachine($initialStateId, StateMachineConfiguration $configuration, ...$extraParams)
    {}

    public function transit(): DeferBoundActionBuilder
    {}

    public function defineTimedState($stateId, int $initialDelay, int $timeInterval, $autoEvent, $autoContext): MutableState
    {}

    public function localTransitions(int $priority = 0): MultiTransitionBuilder
    {}

    public function internalTransition(int $priority = 0): InternalTransitionBuilder
    {}

    public function defineFinalState($stateId): MutableState
    {}

    public function defineParallelStatesOn($parentStateId, $childStateIds): void
    {}

    public function onEntry($stateId): EntryExitActionBuilder
    {}

    public function defineNoInitSequentialStatesOn($parentStateId, $childStateIds, ?HistoryType $historyType = null): void
    {}

    public function localTransition(int $priority = 0): LocalTransitionBuilder
    {}

    /**
     * 
     * {@inheritDoc}
     * @see \Pluf\Workflow\StateMachineBuilder::newStateMachine()
     */
    public function newStateMachine($initialStateId, ?StateMachineConfiguration $configuration = null, array $extraParams = null): StateMachine
    {
        if (! $this->prepared) {
            $this->prepare();
        }
        if (! $this->isValidState($initialStateId)) {
            throw new IllegalArgumentException("Cannot find Initial state \'" . $initialStateId . "\' in state machine.");
        }
        if(!isset($configuration)){
            $configuration = StateMachineConfiguration::create();
        }

        // Class[] constParamTypes = constructor.getParameterTypes();
        $stateMachine = new AbstractStateMachine();
        // try {
        // if(constParamTypes==null || constParamTypes.length==0) {
        // stateMachine = ReflectUtils.newInstance(constructor);
        // } else {
        // stateMachine = ReflectUtils.newInstance(constructor, extraParams);
        // }
        // } catch(SquirrelRuntimeException e) {
        // throw new IllegalStateException(
        // "New state machine instance failed.", e.getTargetException());
        // }

        $stateMachineImpl = $stateMachine;
        $stateMachineImpl->prePostConstruct($initialStateId, $this->states, $configuration /*
         * , new Runnable() {
         * public void run() {
         * stateMachineImpl.setStartEvent(startEvent);
         * stateMachineImpl.setFinishEvent(finishEvent);
         * stateMachineImpl.setTerminateEvent(terminateEvent);
         * stateMachineImpl.setExtraParamTypes(extraParamTypes);
         *
         * stateMachineImpl.setTypeOfStateMachine(stateMachineImplClazz);
         * stateMachineImpl.setTypeOfState(stateClazz);
         * stateMachineImpl.setTypeOfEvent(eventClazz);
         * stateMachineImpl.setTypeOfContext(contextClazz);
         * stateMachineImpl.setScriptManager(scriptManager);
         * }
         * }
         */
        );

        if ($this->postConstructMethod != null /* && $this->extraParamTypes.length==extraParams.length */) {
            // try {
            // ReflectUtils.invoke(postConstructMethod, stateMachine, extraParams);
            // } catch(SquirrelRuntimeException e) {
            // throw new IllegalStateException(
            // "Invoke state machine postConstruct method failed.", e.getTargetException());
            // }
            // TODO: invokde the post constractur
        }
        $this->postProcessStateMachine($this->stateMachineImplClazz, $stateMachine);

        return $stateMachine;
    }

    private function postProcessStateMachine(string $clz, $component)
    {
        if ($component != null) {
            // XXX:
            // $postProcessors = SquirrelPostProcessorProvider.getInstance().getCallablePostProcessors(clz);
            $postProcessors = [];
            foreach ($postProcessors as $postProcessor) {
                $postProcessor->postProcess($component);
            }
        }
        return $component;
    }

    /**
     * Checks if the state exists
     *
     * @param mixed $initialStateId
     * @return bool
     */
    private function isValidState($initialStateId): bool
    {
        // return array_key_exists($initialStateId, $this->states);
        return $this->states->offsetExists($initialStateId);
    }
}

