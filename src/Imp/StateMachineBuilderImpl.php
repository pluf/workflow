<?php
namespace Pluf\Workflow\Imp;

use Pluf\Di\Container;
use Pluf\Workflow\Action;
use Pluf\Workflow\ActionExecutionService;
use Pluf\Workflow\Conditions;
use Pluf\Workflow\HistoryType;
use Pluf\Workflow\MutableState;
use Pluf\Workflow\MutableTransition;
use Pluf\Workflow\StateMachine;
use Pluf\Workflow\StateMachineBuilder;
use Pluf\Workflow\StateMachineConfiguration;
use Pluf\Workflow\TransitionType;
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
use Throwable;
use Pluf\Workflow\Conditions\Always;
use Pluf\Workflow\Conditions\Never;

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
        $stateId = $state->name;
        // Preconditions.checkNotNull(stateId, "Cannot convert state of name \""+state.name()+"\".");
        $newState = $this->defineState($stateId);
        $newState->setCompositeType($state->compositeType);
        if (! $newState->isParallelState()) {
            $newState->setHistoryType($state->historyType);
        }
        $newState->setFinal($state->finalState);

        if (! empty($state->parent)) {
            $parentStateId = $this->parseStateId($state->parent);
            $parentState = $this->defineState($parentStateId);
            $newState->setParentState($parentState);
            $parentState->addChildState($newState);
            if (! $parentState->isParallelState() && $state->initialState) {
                $parentState->setInitialState($newState);
            }
        }

        if (! empty($state->entryCallMethod)) {
            $methodCallAction = FSM::newMethodCallAction($state->entryCallMethod);
            $this->onEntry($stateId)->perform($methodCallAction);
        }

        if (! empty($state->exitCallMethod)) {
            $methodCallAction = FSM::newMethodCallActionProxy($state->exitCallMetho, $this->executionContext);
            $this->onExit($stateId)->perform($methodCallAction);
        }
        $this->rememberStateAlias($state);
    }

    private function buildDeclareTransition(Transit $transit): void
    {
        if (empty($transit)) {
            return;
        }

        // TODO: support sate converters
        // Preconditions.checkState(stateConverter!=null, "Do not register state converter");
        // Preconditions.checkState(eventConverter!=null, "Do not register event converter");

        // if not explicit specify 'from', 'to' and 'event', it is declaring a defer bound action.
        if ($this->isDeferBoundAction($transit)) {
            $this->buildDeferBoundAction($transit);
            return;
        }
        
        $when = $transit->getWhen();
        $this->assertTrue($this->isInstantiableType($when), "Condition \'when\' should be concrete class or static inner class.");
        $this->assertTrue($transit->type != TransitionType::INTERNAL || $transit->from == $transit->to, "Internal transition must transit to the same source state.");

        // $fromState = stateConverter.convertFromString(parseStateId(transit.from()));
        $fromState = $this->parseStateId($transit->from);
        $this->assertNotEmpty($fromState, "Source state not found.");
        // $toState = stateConverter.convertFromString(parseStateId(transit.to()));
        $toState = $this->parseStateId($transit->to);
        // $event = eventConverter.convertFromString(transit.on());
        $event = $transit->on;
        $this->assertNotEmpty($event, "Event not found.");

        // check exited transition which satisfied the criteria
        if ($this->states->offsetExists($fromState)) {
            $theFromState = $this->states[$fromState];
            foreach ($theFromState->getAllTransitions() as $t) {
                if ($t->isMatch($fromState, $toState, $event, $transit->priority, $when, $transit->type)) {
                    $mutableTransition = $t;
                    $callMethodExpression = $transit->callMethod;
                    if (! empty($callMethodExpression)) {
                        $methodCallAction = FSM::newMethodCallAction($callMethodExpression);
                        $mutableTransition->addAction($methodCallAction);
                    }
                    return;
                }
            }
        }

        // if no existed transition is matched then create a new transition
        $toBuilder = null;
        if ($transit->type == TransitionType::INTERNAL) {
            $transitionBuilder = FSM::newInternalTransitionBuilder($this->states, $transit->priority);
            $toBuilder = $transitionBuilder->within($fromState);
        } else {
            $transitionBuilder = ($transit->type == TransitionType::LOCAL) ? FSM::newLocalTransitionBuilder($this->states, $transit->priority) : FSM::newExternalTransitionBuilder($this->states, $transit->priority);
            $fromBuilder = $transitionBuilder->from($fromState);
            $isTargetFinal = $transit->targetFinal || FSM::getState($this->states, $toState)->isFinalState();
            $toBuilder = $isTargetFinal ? $fromBuilder->toFinal($toState) : $fromBuilder->to($toState);
        }
        $onBuilder = $toBuilder->on($event);
        $c = null;
        try {
            if ($transit->when != 'Always') {
                $constructor = $when;
                // TODO: maso, 2021: use invoker to instance
                $c = new $constructor();
            } else if (! empty($transit->whenMvel)) {
                $c = FSM::newMvelCondition($transit->whenMvel);
            }
        } catch (Throwable $e) {
            // logger.error("Instantiate Condition \""+transit.when().getName()+"\" failed.");
            $c = Conditions::never();
        }
        $whenBuilder = $c != null ? $onBuilder->when($c) : $onBuilder;

        if (! empty($transit->callMethod)) {
            $methodCallAction = FSM::newMethodCallAction($transit->callMethod);
            $whenBuilder->perform($methodCallAction);
        }
    }

    /**
     * Checks if many source, distance or event must support
     *
     * @param Transit $transit
     * @return bool
     */
    private function isDeferBoundAction(Transit $transit): bool
    {
        return "*" == $transit->from || "*" == $transit->to || "*" == $transit->on;
    }

    /**
     * add alias
     *
     * @param State $state
     */
    private function rememberStateAlias(State $state): void
    {
        if (empty($state->alias)) {
            return;
        }
        $this->assertFalse(array_key_exists($state->alias, $this->stateAliasToDescription), "Cannot define duplicate state alias \"{state.alias}\" for state \"{state.name}\" and \"{other}\".", [
            'state' => $state,
            'other' => $this->stateAliasToDescription[$state->alias]
        ]);
        $this->stateAliasToDescription[$state->alias] = $state->name;
    }

    /**
     * Convert alias or name into state name
     *
     * State alias starts with #
     *
     * @param string $value
     * @return string
     */
    private function parseStateId(string $value): string
    {
        return (isset($value) && str_starts_with($value, "#")) ? $this->stateAliasToDescription[substr($value, 1)] : $value;
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
        return is_subclass_of($stateMachineClazz, StateMachineImpl::class, true);
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
    {
        throw new \RuntimeException('Not implements');
    }

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

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineTerminateEvent()
     */
    public function defineTerminateEvent($terminateEvent): void
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineStartEvent()
     */
    public function defineStartEvent($startEvent): void
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::onExit()
     */
    public function onExit($stateId): EntryExitActionBuilder
    {
        $this->checkState();
        $state = FSM::getState($this->states, $stateId);
        return FSM::newEntryExitActionBuilder($state, false);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::externalTransitions()
     */
    public function externalTransitions(int $priority = 0): MultiTransitionBuilder
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineLinkedState()
     */
    public function defineLinkedState($stateId, $linkedStateMachineBuilder, $initialLinkedState, $extraParams): MutableState
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineState()
     */
    public function defineState($stateId): MutableState
    {
        $this->checkState();
        return FSM::getState($this->states, $stateId);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::transitions()
     */
    public function transitions(int $priority = 0): MultiTransitionBuilder
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineFinishEvent()
     */
    public function defineFinishEvent($finishEvent): void
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::transition()
     */
    public function transition(int $priority = 0): ExternalTransitionBuilder
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineSequentialStatesOn()
     */
    public function defineSequentialStatesOn($parentStateId, $childStateIds, ?HistoryType $historyType = null): void
    {
        throw new \RuntimeException('Not implements');
    }

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

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\UntypedStateMachineBuilder::newUntypedStateMachine()
     */
    public function newUntypedStateMachine($initialStateId, StateMachineConfiguration $configuration, ...$extraParams)
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::transit()
     */
    public function transit(): DeferBoundActionBuilder
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineTimedState()
     */
    public function defineTimedState($stateId, int $initialDelay, int $timeInterval, $autoEvent, $autoContext): MutableState
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::localTransitions()
     */
    public function localTransitions(int $priority = 0): MultiTransitionBuilder
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::internalTransition()
     */
    public function internalTransition(int $priority = 0): InternalTransitionBuilder
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineFinalState()
     */
    public function defineFinalState($stateId): MutableState
    {
        $this->checkState();
        $newState = $this->defineState($stateId);
        $newState->setFinal(true);
        return $newState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineParallelStatesOn()
     */
    public function defineParallelStatesOn($parentStateId, $childStateIds): void
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::onEntry()
     */
    public function onEntry($stateId): EntryExitActionBuilder
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::defineNoInitSequentialStatesOn()
     */
    public function defineNoInitSequentialStatesOn($parentStateId, $childStateIds, ?HistoryType $historyType = null): void
    {
        throw new \RuntimeException('Not implements');
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineBuilder::localTransition()
     */
    public function localTransition(int $priority = 0): LocalTransitionBuilder
    {
        throw new \RuntimeException('Not implements');
    }

    private function postConstructStateMachine(StateMachineImpl $stateMachine): void
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
            $this->actionExecutionService = new ExecutionServiceImpl($container);
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
        $stateMachine = new StateMachineImpl($initialStateId, $this->states, $configuration);
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

