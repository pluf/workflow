<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\ActionExecutionService;
use Pluf\Workflow\ErrorCodes;
use Pluf\Workflow\ImmutableLinkedState;
use Pluf\Workflow\ImmutableState;
use Pluf\Workflow\StateContext;
use Pluf\Workflow\StateMachine;
use Pluf\Workflow\StateMachineConfiguration;
use Pluf\Workflow\StateMachineContext;
use Pluf\Workflow\StateMachineData;
use Pluf\Workflow\StateMachineDataReader;
use Pluf\Workflow\StateMachineDataWriter;
use Pluf\Workflow\Visitor;
use Pluf\Workflow\Exceptions\IllegalStateException;
use Pluf\Workflow\Exceptions\TransitionException;
use Pluf\Workflow\IO\SCXMLVisitor;
use Pluf\Workflow\Imp\Events\StartEventImpl;
use Pluf\Workflow\Imp\Events\TerminateEventImpl;
use Pluf\Workflow\Imp\Events\TransitionBeginEventImpl;
use Pluf\Workflow\Imp\Events\TransitionCompleteEventImpl;
use Pluf\Workflow\Imp\Events\TransitionDeclinedEventImpl;
use Pluf\Workflow\Imp\Events\TransitionEndEventImpl;
use Pluf\Workflow\Imp\Events\TransitionExceptionEventImpl;
use Throwable;

/**
 * The Abstract state machine provide several extension ability to cover different extension granularity.
 *
 * <ol>
 * <li>Method <b>beforeStateExit</b>/<b>afterStateEntry</b> is used to add custom logic on all kinds of state exit/entry.</li>
 * <li>Method <b>exit[stateName]</b>/<b>entry[stateName]</b> is extension method which is used to add custom logic on specific state.</li>
 * <li>Method <b>beforeTransitionBegin</b>/<b>afterTransitionComplete</b> is used to add custom logic on all kinds of transition
 * accepted all conditions.</li>
 * <li>Method <b>transitFrom[fromStateName]To[toStateName]On[eventName]</b> is used to add custom logic on specific transition
 * accepted all conditions.</li>
 * <li>Method <b>transitFromAnyTo[toStateName]On[eventName]</b> is used to add custom logic on any state transfer to specific target
 * state on specific event happens, so as the <b>transitFrom[fromStateName]ToAnyOn[eventName]</b>, <b>transitFrom[fromState]To[ToStateName]</b>,
 * and <b>on[EventName]</b>.</li>
 * </ol>
 */
class StateMachineImpl implements StateMachine
{
    use AssertTrait;
    use EventHandlerTrait;

    private ?ActionExecutionService $executor = null;

    private ?StateMachineData $data = null;

    private $implementation;

    private string $status = 'INITIALIZED';

    private ?QueuedEvents $queuedEvents;

    // LinkedBlockingDeque
    private ?QueuedEvents $queuedTestEvents;

    // LinkedBlockingDeque
    private bool $processingTestEvent = false;

    private $startEvent, $finishEvent, $terminateEvent;

    // MvelScriptManager
    private $scriptManager;

    // state machine options
    private bool $autoStartEnabled = true;

    private bool $autoTerminateEnabled = true;

    private bool $delegatorModeEnabled = false;

    private int $transitionTimeout = - 1;

    private bool $dataIsolateEnabled = false;

    private bool $debugModeEnabled = false;

    private bool $remoteMonitorEnabled = false;

    private array $extraParamTypes = [];

    private $lastException = null;

    private bool $entryPoint = false;

    // TransitionException
    public function __construct($initialStateId, $states, StateMachineConfiguration $configuration)
    {
        $this->data = FSM::newStateMachineData($states);
        $this->data->write()->setIdentifier($configuration->getIdProvider()
            ->get());
        $this->data->write()->setInitialState($initialStateId);
        $this->data->write()->setCurrentState(null);

        // retrieve options value from state machine configuration
        $this->autoStartEnabled = $configuration->isAutoStartEnabled();
        $this->autoTerminateEnabled = $configuration->isAutoTerminateEnabled();
        $this->dataIsolateEnabled = $configuration->isDataIsolateEnabled();
        $this->debugModeEnabled = $configuration->isDebugModeEnabled();
        $this->delegatorModeEnabled = $configuration->isDelegatorModeEnabled();

        $this->queuedEvents = new QueuedEvents();
        $this->queuedTestEvents = new QueuedEvents();
    }

    private function processEvent($event, $context, StateMachineData $originalData, ExecutionServiceImpl $executionService, bool $DataIsolateEnabled): bool
    {
        $localData = $originalData;
        $fromState = $localData->read()->getCurrentRawState();
        $fromStateId = $fromState->getStateId();
        $toStateId = null;
        try {
            // TODO: maso, useing named method insted of events (remove this one)
            $this->beforeTransitionBegin($fromStateId, $event, $context);

            if ($this->dataIsolateEnabled) {
                // use local data to isolation transition data write
                $localData = FSM::newStateMachineData($originalData->read()->originalStates());
                $localData->dump($originalData->read());
                // XXX: must use in container
            }

            $result = FSM::newResult(false, $fromState, null);
            $stateContext = FSM::newStateContext($this, $localData, $fromState, $event, $context, $result, $executionService);
            $fromState->internalFire($stateContext);
            $toStateId = $result->getTargetState()->getStateId();

            if ($result->isAccepted()) {
                $executionService->execute();
                $localData->write()->setLastState($fromStateId);
                $localData->write()->setCurrentState($toStateId);
                if ($this->dataIsolateEnabled) {
                    // import local data after transition accepted
                    $originalData->dump($localData->read());
                }
                $this->fire('transitionComplete', new TransitionCompleteEventImpl($fromStateId, $toStateId, $event, $context, $this));
                $this->afterTransitionCompleted($fromStateId, $this->getCurrentState(), $event, $context);
                return true;
            } else {
                $this->fire('TransitionDeclined', new TransitionDeclinedEventImpl($fromStateId, $event, $context, $this));
                $this->afterTransitionDeclined($fromStateId, $event, $context);
            }
        } catch (Throwable $e) {
            // set state machine in error status first which means state machine cannot process event anymore
            // unless this exception has been resolved and state machine status set back to normal again.
            $this->setStatus('ERROR');
            // wrap any exception into transition exception
            $this->lastException = ($e instanceof TransitionException) ? $e : new TransitionException('Fail to execute the action', ErrorCodes::FSM_TRANSITION_ERROR, $e, $fromStateId, $toStateId, $event, $context, 'UNKNOWN');
            $this->fire('transitionException', new TransitionExceptionEventImpl($this->lastException, $fromStateId, $localData->read()
                ->getCurrentState(), $event, $context, $this));
            $this->afterTransitionCausedException($fromStateId, $toStateId, $event, $context);
        } finally {
            $executionService->reset();
            $this->fire('transitionEnd', new TransitionEndEventImpl($fromStateId, $toStateId, $event, $context, $this));
            $this->afterTransitionEnd($fromStateId, $this->getCurrentState(), $event, $context);
        }
        return false;
    }

    private function processEvents(): void
    {
        if ($this->isIdle()) {
            $this->setStatus('BUSY');
            try {
                $eventInfo = null;
                $event = null;
                $context = null;
                while (($eventInfo = $this->queuedEvents->poll()) != null) {
                    // TODO: response to cancel operation
                    $event = $eventInfo->first;
                    $context = $eventInfo->second;
                    $this->processEvent($event, $context, $this->data, $this->executor, $this->dataIsolateEnabled);
                }
                $rawState = $this->data->read()->getCurrentRawState();
                if ($this->autoTerminateEnabled && $rawState->isRootState() && $rawState->isFinalState()) {
                    $this->terminate($context);
                }
            } finally {
                if ($this->getStatus() == 'BUSY') {
                    $this->setStatus('IDLE');
                }
            }
        }
    }

    private function internalFire($event, $context, bool $insertAtFirst = false): void
    {
        if ($this->getStatus() == 'INITIALIZED') {
            if ($this->autoStartEnabled) {
                $this->start($context);
            } else {
                throw new IllegalStateException("The state machine is not running.");
            }
        }
        if ($this->getStatus() == 'TERMINATED') {
            throw new IllegalStateException("The state machine is already terminated.");
        }
        if ($this->getStatus() == 'ERROR') {
            throw new IllegalStateException("The state machine is corruptted.");
        }
        if ($insertAtFirst) {
            $this->queuedEvents->addFirst(new EventPair($event, $context));
        } else {
            $this->queuedEvents->addLast(new EventPair($event, $context));
        }
        $this->processEvents();
    }

    public function isEntryPoint(): bool
    {
        return $this->entryPoint;
    }

    /**
     * This is an entry point
     *
     * @param bool $entryPoint
     * @return self
     */
    public function setEntryPoint(bool $entryPoint = true): self
    {
        $this->entryPoint = $entryPoint;
        return $this;
    }

    /**
     * Clean all queued events
     */
    protected function cleanQueuedEvents()
    {
        $this->queuedEvents->clear();
    }

    public function fireEvent($event, $context = null, bool $insertAtFirst = false): self
    {
        $isEntryPoint = $this->isEntryPoint();
        if ($isEntryPoint) {
            StateMachineContext::set($this);
        } else if ($this->delegatorModeEnabled && StateMachineContext::currentInstance() != $this) {
            $currentInstance = StateMachineContext::currentInstance();
            $currentInstance->fire($event, $context);
            return $this;
        }
        try {
            if (StateMachineContext::isTestEvent()) {
                $this->internalTest($event, $context);
            } else {
                $this->internalFire($event, $context, $insertAtFirst);
            }
        } finally {
            if ($isEntryPoint) {
                StateMachineContext::set(null);
            }
        }
        return $this;
    }

    /**
     *
     * @deprecated No need in php
     * @param mixed $event
     * @param mixed $context
     */
    public function untypedFire($event, $context)
    {
        $this->fireEvent($event, $context);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::fireImmediate()
     */
    public function fireImmediate($event, $context): self
    {
        return $this->fireEvent($event, $context, true);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::isRemoteMonitorEnabled()
     */
    public function isRemoteMonitorEnabled(): bool
    {
        return $this->remoteMonitorEnabled;
    }

    private function internalTest($event, $context)
    {
        $this->checkState($this->status != 'ERROR' && $this->status != 'TERMINATED', "Cannot test state machine under " . $this->status . " status.");

        $testResult = null;
        $this->queuedTestEvents->add(new EventPair($event, $context));
        if (! isProcessingTestEvent) {
            $this->processingTestEvent = true;
            $cloneData = $this->dumpSavedData();
            $dummyExecutor = $this->getDummyExecutor();

            if ($this->getStatus() == 'INITIALIZED') {
                if ($this->autoStartEnabled) {
                    $this->internalStart($context, $cloneData, $dummyExecutor);
                } else {
                    throw new IllegalStateException("The state machine is not running.");
                }
            }
            try {
                $eventInfo = null;
                while (($eventInfo = $this->queuedTestEvents->poll()) != null) {
                    $testEvent = $eventInfo->first();
                    $testContext = $eventInfo->second();
                    $this->processEvent($testEvent, $testContext, $cloneData, $dummyExecutor, false);
                }
                $testResult = $this->resolveState($cloneData->read()
                    ->currentState(), $cloneData);
            } finally {
                $this->processingTestEvent = false;
            }
        }
        return $testResult;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::test()
     */
    public function test($event, $context)
    {
        $isEntryPoint = $this->isEntryPoint();
        if ($isEntryPoint) {
            StateMachineContext::set($this, true);
        }
        try {
            return $this->internalTest(event, context);
        } finally {
            if ($isEntryPoint) {
                StateMachineContext::set(null);
            }
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::canAccept()
     */
    public function canAccept($event): bool
    {
        $testRawState = $this->getCurrentRawState();
        if ($testRawState == null) {
            if ($this->autoStartEnabled) {
                $testRawState = $this->getInitialRawState();
            } else {
                return false;
            }
        }
        return array_key_exists($event, $testRawState->getAcceptableEvents());
    }

    /**
     * Checks if the statemachin is in Idle state
     *
     * @return bool
     */
    protected function isIdle(): bool
    {
        return $this->getStatus() != 'BUSY';
    }

    protected function afterTransitionCausedException($from, $to, $event, $context)
    {
        $le = $this->getLastException();
        // if ($le->getTargetException() != null) {
        // $this->logger->error("Transition caused exception", $le->getTargetException());
        // }
        throw $le;
    }

    protected function beforeTransitionBegin($from, $event, $context): void
    {
        // TODO:

        // +
        $this->fire('transitionBegin', new TransitionBeginEventImpl($from, $event, $context, $this));
    }

    protected function afterTransitionCompleted($from, $to, $event, $ontext): void
    {
        // TODO: call registerd callables
    }

    protected function afterTransitionEnd($from, $to, $event, $context): void
    {
        // TODO: call registerd callables
    }

    protected function afterTransitionDeclined($from, $event, $context): void
    {
        // TODO: call registerd callables
    }

    protected function beforeActionInvoked($from, $to, $event, $context): void
    {
        // TODO: call registerd callables
    }

    protected function afterActionInvoked($from, $to, $event, $context): void
    {
        // TODO: call registerd callables
    }

    private function resolveRawState(ImmutableState $rawState): ImmutableState
    {
        $resolvedRawState = $rawState;
        if ($resolvedRawState instanceof ImmutableLinkedState) {
            $resolvedRawState = $rawState->getLinkedStateMachine($this)->getCurrentRawState();
        }
        return $resolvedRawState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getCurrentRawState()
     */
    public function getCurrentRawState(): ImmutableState
    {
        $rawState = $this->data->read()->getCurrentRawState();
        return $this->resolveRawState($rawState);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getLastRawState()
     */
    public function getLastRawState(): ImmutableState
    {
        $lastRawState = $this->data->read()->getLastRawState();
        return $this->resolveRawState($lastRawState);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getInitialRawState()
     */
    public function getInitialRawState(): ImmutableState
    {
        return $this->getRawStateFrom($this->getInitialState());
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getRawStateFrom()
     */
    public function getRawStateFrom($stateId): ImmutableState
    {
        return $this->data->read()->getRawStateFrom($stateId);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getAllRawStates()
     */
    public function getAllRawStates(): array
    {
        return $this->data->read()->getRawStates();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getAllStates()
     */
    public function getAllStates(): array
    {
        return $this->data->read()->getStates();
    }

    /**
     * Gets state from the local data
     *
     * @param mixed $state
     * @param mixed $localData
     * @return string
     */
    private function resolveState($state, $localData)
    {
        $resolvedState = $state;
        $rawState = $localData->read()->getRawStateFrom($resolvedState);
        if ($rawState instanceof ImmutableLinkedState) {
            $resolvedState = $rawState->getLinkedStateMachine($this)->getCurrentState();
        }
        return $resolvedState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getCurrentState()
     */
    public function getCurrentState()
    {
        return $this->resolveState($this->data->read()
            ->getCurrentState(), $this->data);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getLastState()
     */
    public function getLastState()
    {
        return $this->resolveState($this->data->read()
            ->getLastState(), $this->data);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getInitialState()
     */
    public function getInitialState()
    {
        return $this->data->read()->getInitialState();
    }

    /**
     * Finds the state and all other entries
     *
     * A state is entry if it is the parent of the current entry state
     *
     * @param ImmutableState $origin
     * @param StateContext $stateContext
     */
    private function entryAll(ImmutableState $origin, ?StateContext $stateContext)
    {
        $stack = [];

        $state = $origin;
        while ($state != null) {
            array_push($stack, $state);
            $state = $state->getParentState();
        }
        while (! empty($stack)) {
            $state = array_pop($stack);
            $state->entry($stateContext);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::start()
     */
    public function start($context = null): self
    {
        if (! $this->isStarted()) {
            $this->setStatus('BUSY');
            $this->internalStart($context, $this->data, $this->executor);
            $this->setStatus('IDLE');
            $this->processEvents();
        }
        return $this;
    }

    private function internalStart($context, StateMachineData $localData, ActionExecutionService $executionService)
    {
        $initialRawState = $localData->read()->getInitialRawState();
        $stateContext = FSM::newStateContext($this, $localData, $initialRawState, $this->getStartEvent(), $context, null, $executionService);

        $this->entryAll($initialRawState, $stateContext);
        $historyState = $initialRawState->enterByHistory($stateContext);
        $executionService->execute();
        $localData->write()->setCurrentState($historyState->getStateId());
        $localData->write()->setStartContext($context);
        $this->fire('start', new StartEventImpl($this));
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::isStarted()
     */
    public function isStarted(): bool
    {
        return $this->getStatus() == 'IDLE' || $this->getStatus() == 'BUSY';
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::isTerminated()
     */
    public function isTerminated(): bool
    {
        return $this->getStatus() == 'TERMINATED';
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::isError()
     */
    public function isError(): bool
    {
        return $this->getStatus() == 'ERROR';
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getStatus()
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Sets the status of the machine
     *
     * @param string $status
     */
    protected function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getLastActiveChildStateOf()
     */
    public function getLastActiveChildStateOf($parentStateId)
    {
        return $this->data->read()->lastActiveChildStateOf($parentStateId);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getSubStatesOn()
     */
    public function getSubStatesOn($parentStateId): array
    {
        return $this->data->read()->subStatesOn($parentStateId);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::terminate()
     */
    public function terminate($context = null): void
    {
        if ($this->isTerminated()) {
            return;
        }

        $stateContext = FSM::newStateContext($this, $this->data, $this->data->read()->getCurrentRawState(), $this->getTerminateEvent(), $context, null, $this->executor);
        $this->exitAll($this->data->read()
            ->getCurrentRawState(), $stateContext);
        $this->executor->execute();

        $this->setStatus('TERMINATED');
        $this->fire('terminate', new TerminateEventImpl($this));
    }

    private function exitAll(ImmutableState $current, $stateContext)
    {
        $state = $current;
        while ($state != null) {
            $state->exit($stateContext);
            $state = $state->getParentState();
        }
    }

    public function accept(Visitor $visitor): void
    {
        $visitor->visitOnEntry($this);
        forEach ($this->getAllRawStates() as $state) {
            if ($state->getParentState() == null) {
                $state->accept(visitor);
            }
        }
        $visitor->visitOnExit($this);
    }

    /**
     * Set type of state machine
     *
     * State machine type is a class that implements all functional parts of FSM.
     *
     * @param string $stateMachineType
     *            to use
     */
    public function setTypeOfStateMachine($stateMachineType): self
    {
        $this->data->write()->setTypeOfStateMachine($stateMachineType);
        return $this;
    }

    /**
     * Sets type of states.
     *
     * @param string $stateType
     * @return self
     */
    public function setTypeOfState(string $stateType): self
    {
        $this->data->write()->setTypeOfState($stateType);
        return $this;
    }

    /**
     * Sets type of events
     *
     * @param string $eventType
     * @return self
     */
    public function setTypeOfEvent(string $eventType): self
    {
        $this->data->write()->setTypeOfEvent($eventType);
        return $this;
    }

    /**
     * Sets type of context
     *
     * @deprecated context must pass throw the DI
     * @param string $contextType
     * @return self
     */
    public function setTypeOfContext(?string $contextType): self
    {
        $this->data->write()->setTypeOfContext($contextType);
        return $this;
    }

    /**
     * Sets script manager
     *
     * @param mixed $scriptManager
     * @return self
     */
    public function setScriptManager($scriptManager): self
    {
        $this->assertEmpty($this->scriptManager);
        $this->scriptManager = $scriptManager;
        return $this;
    }

    /**
     * Sets start event
     *
     * @param mixed $startEvent
     * @return self
     */
    public function setStartEvent($startEvent): self
    {
        $this->assertEmpty($this->startEvent);
        $this->startEvent = $startEvent;
        return $this;
    }

    function getStartEvent()
    {
        return $this->startEvent;
    }

    /**
     * Sets termination event
     *
     * @param mixed $terminateEvent
     * @return self
     */
    public function setTerminateEvent($terminateEvent): self
    {
        $this->assertEmpty($this->terminateEvent);
        $this->terminateEvent = $terminateEvent;
        return $this;
    }

    function getTerminateEvent()
    {
        return $this->terminateEvent;
    }

    /**
     * Sets finis events
     *
     * @param mixed $finishEvent
     * @return self
     */
    public function setFinishEvent($finishEvent): self
    {
        $this->assertEmpty($this->finishEvent);
        $this->finishEvent = $finishEvent;
        return $this;
    }

    function getFinishEvent()
    {
        return $this->finishEvent;
    }

    /**
     *
     * @deprecated not case in PHP
     * @param mixed $extraParamTypes
     */
    function setExtraParamTypes($extraParamTypes): self
    {
        $this->assertEmpty($this->extraParamTypes);
        $this->extraParamTypes = $extraParamTypes;
        return $this;
    }

    public function isContextSensitive(): bool
    {
        return true;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::typeOfContext()
     */
    public function typeOfContext(): string
    {
        return $this->data->read()->typeOfContext();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::typeOfEvent()
     */
    public function typeOfEvent(): string
    {
        return $this->data->read()->typeOfEvent();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::typeOfState()
     */
    public function typeOfState(): string
    {
        return $this->data->read()->typeOfState();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getLastException()
     */
    public function getLastException(): TransitionException
    {
        return $this->lastException;
    }

    protected function setLastException(TransitionException $lastException)
    {
        $this->lastException = $lastException;
    }

    /**
     * Internal use only
     *
     * @return int size of exector
     */
    public function getExecutorListenerSize(): int
    {
        return $this->executor->getListenerSize();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getIdentifier()
     */
    public function getIdentifier(): string
    {
        return $this->data->read()->identifier();
    }

    /**
     * Sets the action executer
     *
     * @param ActionExecutionService $actionExecutionService
     * @return self
     */
    public function setActionExecutionService(ActionExecutionService $actionExecutionService): self
    {
        $this->assertEmpty($this->executor, 'Trying to set executor twic');
        $this->executor = $actionExecutionService;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getImplementation()
     */
    public function getImplementation()
    {
        if (! isset($this->implementation)) {
            // New instance
            $type = $this->data->read()->getTypeOfStateMachine();
            // TODO: use invoker to instance the state machine
            $this->implementation = new $type();
        }
        return $this->implementation;
    }

    /**
     * Sets state machine implementation
     *
     * @param mixed $stateMachineImplementation
     */
    public function setImplementation($stateMachineImplementation)
    {
        $this->assertEmpty($this->implementation, 'Trying to set implimentation twic');
        $this->implementation = $stateMachineImplementation;
    }

    // private interface DeclarativeListener {
    // Object getListenTarget();
    // }

    // private Object newListenerMethodProxy(final Object listenTarget,
    // final Method listenerMethod, final Class listenerInterface, final String condition) {
    // final String listenerMethodName = ReflectUtils.getStatic(
    // ReflectUtils.getField(listenerInterface, "METHOD_NAME")).toString();
    // AsyncExecute asyncAnnotation = ReflectUtils.getAnnotation(listenTarget.getClass(), AsyncExecute.class);
    // if(asyncAnnotation==null) {
    // asyncAnnotation = listenerMethod.getAnnotation(AsyncExecute.class);
    // }
    // final boolean isAsync = asyncAnnotation!=null;
    // final long timeout = asyncAnnotation!=null ? asyncAnnotation.timeout() : -1;
    // InvocationHandler invocationHandler = new InvocationHandler() {
    // @Override
    // public Object invoke(Object proxy, Method method, Object[] args) throws Throwable {
    // if(method.getName().equals("getListenTarget")) {
    // return listenTarget;
    // } else if(method.getName().equals(listenerMethodName)) {
    // if(args[0] instanceof TransitionEvent) {
    // @SuppressWarnings("unchecked")
    // TransitionEvent<T, S, E, C> event = (TransitionEvent<T, S, E, C>)args[0];
    // return invokeTransitionListenerMethod(listenTarget, listenerMethod, condition, event);
    // } else if(args[0] instanceof ActionEvent) {
    // @SuppressWarnings("unchecked")
    // ActionEvent<T, S, E, C> event = (ActionEvent<T, S, E, C>)args[0];
    // return invokeActionListenerMethod(listenTarget, listenerMethod, condition, event);
    // } else if(args[0] instanceof StartEvent || args[0] instanceof TerminateEvent) {
    // @SuppressWarnings("unchecked")
    // StateMachineEvent<T, S, E, C> event = (StateMachineEvent<T, S, E, C>)args[0];
    // return invokeStateMachineListenerMethod(listenTarget, listenerMethod, condition, event);
    // } else {
    // throw new IllegalArgumentException("Unable to recognize argument type "+args[0].getClass().getName()+".");
    // }
    // } else if(method.getName().equals("equals")) {
    // return super.equals(args[0]);
    // } else if(method.getName().equals("hashCode")) {
    // return super.hashCode();
    // } else if(method.getName().equals("toString")) {
    // return super.toString();
    // } else if(isAsync && method.getName().equals("timeout")) {
    // return timeout;
    // }
    // throw new UnsupportedOperationException("Cannot invoke method "+method.getName()+".");
    // }
    // };
    // Class[] implementedInterfaces = isAsync ?
    // new Class[]{listenerInterface, DeclarativeListener.class, AsyncEventListener.class} :
    // new Class[]{listenerInterface, DeclarativeListener.class};
    // Object proxyListener = Proxy.newProxyInstance(StateMachine.class.getClassLoader(),
    // implementedInterfaces, invocationHandler);
    // return proxyListener;
    // }

    // ------------------------------------------------------------------------------
    // IO
    // ------------------------------------------------------------------------------
    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::getDescription()
     */
    public function getDescription(): string
    {
        $read = $this->data->read();
        $description = '';
        $description .= "id=\"" . $read->identifier() . "\" ";
        $description .= "fsm-type=\"" . $read->getTypeOfStateMachine() . "\" ";
        $description .= "state-type=\"" . $read->getTypeOfState() . "\" ";
        $description .= "event-type=\"" . $read->getTypeOfEvent() . "\" ";
        $description .= "context-type=\"" . $read->getTypeOfContext() . "\" ";

        // Converter<E> eventConverter = ConverterProvider.INSTANCE.getConverter(typeOfEvent());
        // if(getStartEvent()!=null) {
        // builder.append("start-event=\"");
        // builder.append(eventConverter.convertToString(getStartEvent()));
        // builder.append("\" ");
        // }
        // if(getTerminateEvent()!=null) {
        // builder.append("terminate-event=\"");
        // builder.append(eventConverter.convertToString(getTerminateEvent()));
        // builder.append("\" ");
        // }
        // if(getFinishEvent()!=null) {
        // builder.append("finish-event=\"");
        // builder.append(eventConverter.convertToString(getFinishEvent()));
        // builder.append("\" ");
        // }
        // builder.append("context-insensitive=\"").append(isContextSensitive()).append("\" ");

        // if(extraParamTypes!=null && extraParamTypes.length>0) {
        // builder.append("extra-parameters=\"[");
        // for(int i=0; i<extraParamTypes.length; ++i) {
        // if(i>0) builder.append(",");
        // builder.append(extraParamTypes[i].getName());
        // }
        // builder.append("]\" ");
        // }
        return $description;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::exportXMLDefinition()
     */
    public function exportXMLDefinition(bool $beautifyXml): string
    {
        // SquirrelProvider.getInstance().newInstance(SCXMLVisitor.class);
        // TODO: get from DI
        $visitor = new SCXMLVisitor();
        $this->accept($visitor);
        return $visitor->getScxml($beautifyXml);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::dumpSavedData()
     */
    public function dumpSavedData(): StateMachineDataReader
    {
        $savedData = FSM::newStateMachineData($this->data->read()->originalStates());
        $savedData->dump($this->data->read());

        // process linked state if any
        $this->saveLinkedStateData($this->data->read(), $savedData->write());
        return $savedData->read();
    }

    private function saveLinkedStateData(StateMachineDataReader $src, StateMachineDataWriter $target)
    {
        $this->dumpLinkedStateFor($src->currentRawState(), $target);
        // dumpLinkedStateFor(src.lastRawState(), target);
        // TODO-hhe: dump linked state info for last active child state
        // TODO-hhe: dump linked state info for parallel state
    }

    private function dumpLinkedStateFor(ImmutableState $rawState, StateMachineDataWriter $target)
    {
        if ($rawState != null && $rawState instanceof ImmutableLinkedState) {
            $linkStateData = $rawState->getLinkedStateMachine($this)->dumpSavedData();
            $target->linkedStateDataOn($rawState->getStateId(), $linkStateData);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachine::loadSavedData()
     */
    public function loadSavedData(StateMachineDataReader $savedData): bool
    {
        // Preconditions.checkNotNull(savedData, "Saved data cannot be null");
        $this->data->dump($savedData);
        // process linked state if any
        forEach ($savedData->linkedStates() as $linkedState) {
            $linkedStateData = $savedData->linkedStateDataOf($linkedState);
            $rawState = $this->data->read()->rawStateFrom($linkedState);
            if ($linkedStateData != null && $rawState instanceof ImmutableLinkedState) {
                $rawState->getLinkedStateMachine($this)->loadSavedData($linkedStateData);
            }
        }
        $this->setStatus('IDLE');
        return true;
    }
}







































