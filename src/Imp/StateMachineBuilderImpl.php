<?php
namespace Pluf\Workflow\Imp;

use Pluf\Di\Container;
use Pluf\Workflow\Action;
use Pluf\Workflow\ActionExecutionService;
use Pluf\Workflow\HistoryType;
use Pluf\Workflow\MutableState;
use Pluf\Workflow\MutableTransition;
use Pluf\Workflow\StateMachine;
use Pluf\Workflow\StateMachineBuilder;
use Pluf\Workflow\StateMachineConfiguration;
use Pluf\Workflow\UntypedStateMachineBuilder;
use Pluf\Workflow\Actions\FinalStateGuardAction;
use Pluf\Workflow\Attributes\State;
use Pluf\Workflow\Attributes\Transit;
use Pluf\Workflow\Builder\DeferBoundActionBuilder;
use Pluf\Workflow\Builder\EntryExitActionBuilder;
use Pluf\Workflow\Builder\ExternalTransitionBuilder;
use Pluf\Workflow\Builder\InternalTransitionBuilder;
use Pluf\Workflow\Builder\LocalTransitionBuilder;
use Pluf\Workflow\Builder\MultiTransitionBuilder;
use Pluf\Workflow\Component\IdProviderUUID;
use Pluf\Workflow\Exceptions\IllegalArgumentException;
use Pluf\Workflow\Exceptions\IllegalStateException;
use ArrayObject;
use ReflectionClass;

class StateMachineBuilderImpl implements UntypedStateMachineBuilder, StateMachineBuilder
{
    use AssertTrait;

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

    // Not supported, a DI system is replaced
    private ?ActionExecutionService $actionExecutionService = null;

    // ExecutionContext
    private array $deferBoundActionInfoList = [];

    private bool $scanAnnotations = true;

    private array $extraParamTypes = [];

    private ?StateMachineConfiguration $stateMachineConfiguration = null;

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

    /**
     * Prepares the builder
     *
     * It may load all descriptions from the state machine class and merge with
     * flute api.
     */
    private function prepare(): void
    {
        if ($this->prepared) {
            return;
        }

        $container = $this->getContainer();
        $container['stateMachinBuilder'] = Container::value($this);

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
        if ($this->hasMethod($this->stateMachineImplClazz, $methodName)) {
            $methodCallAction = FSM::newMethodCallAction($methodName, Action::EXTENSION_WEIGHT);
            $mutableTransition->addAction($methodCallAction);
        }
    }

    private function addStateEntryExitMethodCallAction(string $methodName, $parameterTypes, MutableState $mutableState, bool $isEntryAction): void
    {
        if ($this->hasMethod($this->stateMachineImplClazz, $methodName)) {
            $weight = Action::EXTENSION_WEIGHT;
            if (str_starts_with($methodName, "before")) {
                $weight = Action::BEFORE_WEIGHT;
            } else if (str_starts_with($methodName, "after")) {
                $weight = Action::AFTER_WEIGHT;
            }
            $methodCallAction = FSM::newMethodCallAction($methodName, $weight);
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
        return [
            "before" . $postfix,
            $prefix . ucfirst(($this->stateConverter != null && ! $state->isFinalState()) ? $this->stateConverter->convertToString($state->getStateId()) : $state),
            "after" . $postfix
        ];
    }

    private function getTransitionMethodNames($transition): array
    {
        $fromState = $transition->getSourceState();
        $toState = $transition->getTargetState();
        $event = $transition->getEvent();
        $fromStateName = ucfirst($this->stateConverter != null ? $this->stateConverter . convertToString($fromState->getStateId()) : $fromState);
        $toStateName = ucfirst(($this->stateConverter != null && ! $toState->isFinalState()) ? $this->stateConverter->convertToString($toState->getStateId()) : $toState);
        $eventName = ucfirst($this->eventConverter != null ? $this->eventConverter->convertToString($event) : $event);
        $conditionName = ucfirst($transition->getCondition()->name());
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

    /**
     *
     * @deprecated use searchMethod
     * @param string $target
     * @param string $methodName
     * @return NULL
     */
    protected function findMethodCallActionInternal(string $target, string $methodName)
    {
        return $this->hasMethod($target, $methodName);
    }

    protected function hasMethod(string $targetClass, string $name)
    {
        $reflectionClass = new ReflectionClass($targetClass);
        return $reflectionClass->hasMethod($name);
    }

    public function setStateMachinClass($stateMachineClass): self
    {
        $this->checkState();
        $this->stateMachineImplClazz = $stateMachineClass;
        return $this;
    }

    public function setContainer(Container $container): self
    {
        $this->checkState();
        $this->container = $container;
        return $this;
    }

    protected function getContainer(): Container
    {
        if (! isset($this->container)) {
            $this->container = new Container();
        }
        return $this->container;
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
        return FSM::newExternalTransitionBuilder($this->states, $priority);
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

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::setStateMachineConfiguration()
     */
    public function setStateMachineConfiguration(?StateMachineConfiguration $configure = null): self
    {
        $this->stateMachineConfiguration = $configure;
    }

    /**
     * Gets the state machine configurateion
     *
     * A new instance will be created if no configuration has not setted
     *
     * @return StateMachineConfiguration the configuration
     */
    protected function getStateMachineConfiguration(): StateMachineConfiguration
    {
        if (! isset($this->stateMachineConfiguration)) {
            $this->stateMachineConfiguration = StateMachineConfiguration::create();
            $this->stateMachineConfiguration->setIdProvider(new IdProviderUUID());
        }
        return $this->stateMachineConfiguration;
    }

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

    private function postConstructStateMachine(AbstractStateMachine $stateMachine): void
    {
        if (isset($this->postConstructMethod)) {
            // TODO: invokde the post constractur
            // the method must be in the state machine implementation
        }
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
        return $this->states->offsetExists($initialStateId);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::setScanAnnotations()
     */
    public function setScanAnnotations(bool $scanAnnotations): self
    {
        $this->scanAnnotations = $scanAnnotations;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::setActionExecutionService()
     */
    public function setActionExecutionService(ActionExecutionService $actionExecutionService): self
    {
        $this->actionExecutionService = $actionExecutionService;
        return $this;
    }

    /**
     * Gets execution service
     *
     * @return ActionExecutionService
     */
    protected function getActionExecutionService(): ActionExecutionService
    {
        if (! isset($this->actionExecutionService)) {
            $container = $this->getContainer();
            $this->actionExecutionService = new AbstractExecutionService($container);
        }
        return $this->actionExecutionService;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::newStateMachine()
     */
    public function build($initialStateId, ?array $extraParams = null): StateMachine
    {
        $this->prepare();
        if (! $this->isValidState($initialStateId)) {
            throw new IllegalArgumentException("Cannot find Initial state \'" . $initialStateId . "\' in state machine.");
        }

        $configuration = $this->getStateMachineConfiguration();
        $actionExecutionService = $this->getActionExecutionService();

        // Just internal implementaion allowed
        $stateMachine = new AbstractStateMachine($initialStateId, $this->states, $configuration);
        $stateMachine->setStartEvent($this->startEvent)
            ->setFinishEvent($this->finishEvent)
            ->setTerminateEvent($this->terminateEvent)
            ->setExtraParamTypes($this->extraParamTypes)
            ->setTypeOfContext($this->contextType)
            ->setTypeOfStateMachine($this->stateMachineImplClazz)
            ->setTypeOfState($this->stateType)
            ->setTypeOfEvent($this->eventType)
            ->setScriptManager($this->scriptManager)
            ->setActionExecutionService($actionExecutionService)
            ->setEntryPoint(true);

        // TODO: move to the state machin constructor
        $this->postConstructStateMachine($stateMachine);
        $this->postProcessStateMachine($this->stateMachineImplClazz, $stateMachine);
        return $stateMachine;
    }
}

