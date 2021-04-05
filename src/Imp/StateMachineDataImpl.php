<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\ImmutableState;
use Pluf\Workflow\StateMachineData;
use Pluf\Workflow\StateMachineDataReader;
use Pluf\Workflow\StateMachineDataWriter;
use ArrayObject;

class StateMachineDataImpl implements StateMachineData, StateMachineDataReader, StateMachineDataWriter
{
    use AssertTrait;

    // private static final Logger logger = LoggerFactory.getLogger(StateMachineDataImpl.class);
    private $currentState;

    private $lastState;

    private $initialState;

    private array $lastActiveChildStateStore = [];

    private $parallelStatesStore = [];

    private ?string $stateMachineType = null;

    private ?string $stateType = null;

    private ?string $eventType = null;

    private ?string $contextType = null;

    private ?string $identifier = null;

    private $startContext;

    private ?ArrayObject $states;

    private array $linkStateDataStore = [];

    public function __construct(?ArrayObject $states = null)
    {
        $this->states = $states;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getOriginalStates()
     */
    public function getOriginalStates(): ArrayObject
    {
        if ($this->states == null) {
            return [];
        }
        return $this->states;
    }

    private function clear(): void
    {
        $this->currentState = null;
        $this->lastState = null;
        $this->initialState = null;
        $this->stateMachineType = null;
        $this->stateType = null;
        $this->eventType = null;
        $this->contextType = null;
        $this->identifier = null;
        $this->startContext = null;
        $this->lastActiveChildStateStore = [];
        $this->parallelStatesStore = [];
        $this->linkStateDataStore = [];
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineData::dump()
     */
    public function dump(StateMachineDataReader $src): void
    {
        $this->clear();

        $this->write()->typeOfStateMachine($src->typeOfStateMachine());
        $this->write()->typeOfState($src->typeOfState());
        $this->write()->typeOfEvent($src->typeOfEvent());
        $this->write()->typeOfContext($src->typeOfContext());

        $this->write()->identifier($src->identifier());
        $this->write()->currentState($src->currentState());
        $this->write()->lastState($src->lastState());
        $this->write()->initialState($src->initialState());
        // write start context of state machine
        $this->write()->startContext($src->startContext());

        forEach ($src->activeParentStates() as $state) {
            $lastActiveChildState = $src->lastActiveChildStateOf($state);
            if ($lastActiveChildState != null) {
                $this->write()->lastActiveChildStateFor($state, $lastActiveChildState);
            }
        }

        forEach ($src->parallelStates() as $state) {
            $subStates = $src->subStatesOn($state);
            if ($subStates != null && ! $subStates->isEmpty()) {
                foreach ($subStates as $subState) {
                    // ignore parallel state check in subStateFor as no states
                    // for reference
                    // this.write().subStateFor(state, subState);
                    $this->parallelStatesStore->put($state, $subState);
                }
            }
        }
    }

    private function getLinkedStateData(): array
    {
        return $this->linkStateDataStore;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineData::read()
     */
    public function read(): StateMachineDataReader
    {
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineData::write()
     */
    public function write(): StateMachineDataWriter
    {
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setCurrentState()
     */
    public function setCurrentState($currentStateId): void
    {
        $this->currentState = $currentStateId;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setLastState()
     */
    public function setLastState($lastStateId): void
    {
        $this->lastState = $lastStateId;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setInitialState()
     */
    public function setInitialState($initialStateId): void
    {
        $this->initialState = $initialStateId;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setStartContext()
     */
    public function setStartContext($context): void
    {
        $this->startContext = $context;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setLastActiveChildStateFor()
     */
    public function setLastActiveChildStateFor($parentStateId, $childStateId): void
    {
        // lastActiveChildStateStore.put(parentStateId, childStateId);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setSubStateFor()
     */
    public function setSubStateFor($parentStateId, $subStateId): void
    {
        // if (rawStateFrom(parentStateId) != null
        // && rawStateFrom(parentStateId).isParallelState()) {
        // parallelStatesStore.put(parentStateId, subStateId);
        // } else {
        // logger.warn("Cannot set sub states on none parallel state {}.",
        // parentStateId);
        // }
    }

    public function removeSubState($parentStateId, $subStateId): void
    {
        // if (rawStateFrom(parentStateId) != null
        // && rawStateFrom(parentStateId).isParallelState()) {
        // parallelStatesStore.remove(parentStateId, subStateId);
        // } else {
        // logger.warn("Cannot remove sub states on none parallel state {}.",
        // parentStateId);
        // }
    }

    public function removeSubStatesOn($parentStateId): void
    {
        // if (rawStateFrom(parentStateId).isParallelState()) {
        // parallelStatesStore.removeAll(parentStateId);
        // }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setIdentifier()
     */
    public function setIdentifier(string $id): void
    {
        $this->identifier = $id;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getIdentifier()
     */
    public function getIdentifier(): String
    {
        return $this->identifier;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getCurrentState()
     */
    public function getCurrentState()
    {
        return $this->currentState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getLastState()
     */
    public function getLastState()
    {
        return $this->lastState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getInitialState()
     */
    public function getInitialState()
    {
        return $this->initialState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getLastActiveChildStateOf()
     */
    public function getLastActiveChildStateOf($parentStateId)
    {
        return $this->lastActiveChildStateStore[$parentStateId];
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getStartContext()
     */
    public function getStartContext()
    {
        return $this->startContext;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getActiveParentStates()
     */
    public function getActiveParentStates(): array
    {
        return array_keys($this->lastActiveChildStateStore);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getSubStatesOn()
     */
    public function getSubStatesOn($parentStateId): array
    {
        if (! array_key_exists($parentStateId, $this->parallelStatesStore)) {
            return [];
        }
        return $this->parallelStatesStore[$parentStateId];
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getCurrentRawState()
     */
    public function getCurrentRawState(): ?ImmutableState
    {
        return $this->currentState != null ? $this->getRawStateFrom($this->currentState) : null;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getLastRawState()
     */
    public function getLastRawState(): ImmutableState
    {
        return $this->getRawStateFrom($this->lastState);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getRawStateFrom()
     */
    public function getRawStateFrom($stateId): ?ImmutableState
    {
        if (! isset($stateId)) {
            return null;
        }
        $states = $this->getOriginalStates();
        // if (array_key_exists($stateId, $states)) {
        if ($states->offsetExists($stateId)) {
            return $states[$stateId];
        }
        return null;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getInitialRawState()
     */
    public function getInitialRawState(): ?ImmutableState
    {
        return $this->getRawStateFrom($this->getInitialState());
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getTypeOfStateMachine()
     */
    public function getTypeOfStateMachine(): ?string
    {
        return $this->stateMachineType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getTypeOfState()
     */
    public function getTypeOfState(): string
    {
        return $this->stateType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getTypeOfEvent()
     */
    public function getTypeOfEvent(): string
    {
        return $this->eventType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getTypeOfContext()
     */
    public function getTypeOfContext(): string
    {
        return $this->contextType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setTypeOfStateMachine()
     */
    public function setTypeOfStateMachine(string $stateMachineType): void
    {
        $this->assertEmpty($this->stateMachineType, "State machine type is set before");
        $this->stateMachineType = $stateMachineType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setTypeOfState()
     */
    public function setTypeOfState(string $stateType): void
    {
        $this->assertEmpty($this->stateType, "State type is set before");
        $this->stateType = $stateType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setTypeOfEvent()
     */
    public function setTypeOfEvent(string $eventType): void
    {
        $this->assertEmpty($this->eventType, "Event type is set before");
        $this->eventType = $eventType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setTypeOfContext()
     */
    public function setTypeOfContext(?string $contextType): void
    {
        $this->assertEmpty($this->contextType, "Context type is set before");
        $this->contextType = $contextType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getRawStates()
     */
    public function getRawStates(): array
    {
        // return array_values($this->getOriginalStates());
        return $this->states->getArrayCopy();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getStates()
     */
    public function getStates(): array
    {
        return array_keys($this->getOriginalStates());
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getParallelStates()
     */
    public function getParallelStates(): array
    {
        return array_keys($this->parallelStatesStore);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getLinkedStates()
     */
    public function getLinkedStates(): array
    {
        return array_keys($this->linkStateDataStore);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataReader::getLinkedStateDataOf()
     */
    public function getLinkedStateDataOf($linkedState): StateMachineDataReader
    {
        if (array_key_exists($linkedState, $this->linkStateDataStore)) {
            return $this->linkStateDataStore[$linkedState];
        }
        return null;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\StateMachineDataWriter::setLinkedStateDataOn()
     */
    public function setLinkedStateDataOn($linkedState, StateMachineDataReader $linkStateData)
    {
        $this->linkStateDataStore[$linkedState] = $linkStateData;
    }
}

