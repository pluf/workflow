<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\HistoryType;
use Pluf\Workflow\ImmutableState;
use Pluf\Workflow\ImmutableTransition;
use Pluf\Workflow\MutableState;
use Pluf\Workflow\MutableTransition;
use Pluf\Workflow\StateCompositeType;
use Pluf\Workflow\StateContext;
use Pluf\Workflow\StateMachineDataReader;
use Pluf\Workflow\Visitor;
use Pluf\Workflow\Conditions\Never;
use Pluf\Workflow\Exceptions\IllegalArgumentException;
use Pluf\Workflow\Exceptions\IllegalStateException;
use Pluf\Workflow\Exceptions\UnsupportedOperationException;

class StateImpl implements MutableState
{

    // private static final Logger logger = LoggerFactory.getLogger(StateImpl.class);
    protected $stateId;

    protected array $entryActions = [];

    protected array $exitActions = [];

    private array $transitions = [];

    private array $acceptableEvents = [];

    /**
     * The super-state of this state.
     * Null for states with <code>level</code> equal to 1.
     */
    private ?ImmutableState $parentState = null;

    private array $childStates = [];

    /**
     * The initial child state of this state.
     */
    private ?ImmutableState $childInitialState = null;

    /**
     * The HistoryType of this state.
     */
    private string $historyType = HistoryType::NONE;

    /**
     * The level of this state within the state hierarchy [1..maxLevel].
     */
    private int $level = 0;

    /**
     * Whether the state is a final state
     */
    private bool $finalState = false;

    /**
     * Composite type of child states
     */
    private $compositeType = StateCompositeType::SEQUENTIAL;

    /**
     * Creates new instance of the state
     *
     * @param mixed $stateId
     */
    public function __construct($stateId)
    {
        $this->stateId = $stateId;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getStateId()
     */
    public function getStateId()
    {
        return $this->stateId;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getEntryActions()
     */
    public function getEntryActions(): array
    {
        return $this->entryActions;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getExitActions()
     */
    public function getExitActions(): array
    {
        return $this->exitActions;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getAllTransitions()
     */
    public function getAllTransitions(): array
    {
        $result = [];
        foreach ($this->transitions as $list) {
            $result = array_merge($result, $list);
        }
        return $result;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getTransitions()
     */
    public function getTransitions($event): array
    {
        if (! array_key_exists($event, $this->transitions)) {
            return [];
        }
        return $this->transitions[$event];
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getAcceptableEvents()
     */
    public function getAcceptableEvents(): array
    {
        // if($this->acceptableEvents==null) {
        // $events = [];
        // events.addAll(getTransitions().keySet());
        // acceptableEvents = Collections.unmodifiableSet(events);
        // }
        return $this->acceptableEvents;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::prioritizeTransitions()
     */
    public function prioritizeTransitions(): void
    {
        foreach ($this->transitions as $trans) {
            usort($trans, function ($a, $b) {
                return $a->priority - $b->priority;
            });
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::entry()
     */
    public function entry(StateContext $stateContext): void
    {
        $stateContext->getExecutor()->begin("STATE_ENTRY__" . $this->stateId);
        $actions = $this->entryActions;
        $event = $stateContext->getEvent();
        $context = $stateContext->getContext();
        $stateMachine = $stateContext->getStateMachine();

        foreach ($actions as $entryAction) {
            $stateContext->getExecutor()->defer($entryAction, null, $this->stateId, $event, $context, $stateMachine);
        }

        if ($this->isParallelState()) {
            // When a parallel state group is entered, all its child states will be simultaneously entered.
            $states = $this->childStates;
            foreach ($states as $parallelState) {
                $parallelState->entry($stateContext);
                $subState = $parallelState->enterByHistory($stateContext);
                $stateContext->getStateMachineData()
                    ->write()
                    ->setSubStateFor($this->stateId, $subState->getStateId());
            }
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::exit()
     */
    public function exit(StateContext $stateContext): void
    {
        if ($this->isParallelState()) {
            $subStates = $this->getSubStatesOn($this, $stateContext->getStateMachineData()
                ->read());
            foreach ($subStates as $subState) {
                if (! $subState->isFinalState()) {
                    $subState->exit($stateContext);
                }
                if ($subState->getParentState() != $this)
                    $subState->getParentState()->exit($stateContext);
            }
            $stateContext->getStateMachineData()
                ->write()
                ->removeSubStatesOn($this->getStateId());
        }

        if ($this->isFinalState()) {
            return;
        }

        $stateContext->getExecutor()->begin("STATE_EXIT__" . $this->stateId);
        foreach ($this->getExitActions() as $exitAction) {
            $stateContext->getExecutor()->defer($exitAction, $this->stateId, null, $stateContext->getEvent(), $stateContext->getContext(), $stateContext->getStateMachine());
        }

        if ($this->getParentState() != null) {
            // update historical state
            $parent = $this->getParentState();
            $shouldUpdateHistoricalState = $parent->getHistoryType() != HistoryType::NONE;
            if (! $shouldUpdateHistoricalState) {
                $iter = $parent->getParentState();
                while ($iter != null) {
                    if ($iter->getHistoryType() == HistoryType::DEEP) {
                        $shouldUpdateHistoricalState = true;
                        break;
                    }
                    $iter = $iter->getParentState();
                }
            }
            if ($shouldUpdateHistoricalState) {
                $stateContext->getStateMachineData()
                    ->write()
                    ->lastActiveChildStateFor($this->getParentState()
                    ->getStateId(), $this->stateId);
            }

            if ($this->getParentState()->isRegion()) {
                $grandParentId = $this->getParentState()
                    ->getParentState()
                    ->getStateId();
                $stateContext->getStateMachineData()
                    ->write()
                    ->removeSubState($grandParentId, $this->stateId);
            }
        }
        // logger.debug("State \""+getStateId()+"\" exit.");
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getParentState()
     */
    public function getParentState(): ?ImmutableState
    {
        return $this->parentState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getChildStates()
     */
    public function getChildStates(): array
    {
        return $this->childStates;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::hasChildStates()
     */
    public function hasChildStates(): bool
    {
        return ! empty($this->childStates);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::setParentState()
     */
    public function setParentState(MutableState $parent): void
    {
        if ($this == $parent) {
            throw new IllegalArgumentException("parent state cannot be state itself.");
        }
        if ($this->parentState == null) {
            $this->parentState = parent;
            $this->setLevel($this->parentState != null ? $this->parentState->getLevel() + 1 : 1);
        } else {
            throw new UnsupportedOperationException("Cannot change state parent.");
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getInitialState()
     */
    public function getInitialState(): ImmutableState
    {
        return $this->childInitialState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::setInitialState()
     */
    public function setInitialState(MutableState $childInitialState): void
    {
        if ($this->isParallelState()) {
            // logger . warn("Ignoring attempt to set initial state of parallel state group.");
            return;
        }
        if ($this->childInitialState == null) {
            $this->childInitialState = $childInitialState;
        } else {
            throw new UnsupportedOperationException("Cannot change child initial state.");
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::enterByHistory()
     */
    public function enterByHistory($stateContext): ImmutableState
    {
        if ($this->finalState || $this->isParallelState()) // no historical info
            return this;

        $result = null;
        switch ($this->historyType) {
            case HistoryType::NONE:
                $result = $this->enterHistoryNone($stateContext);
                break;
            case HistoryType::SHALLOW:
                $result = $this->enterHistoryShallow($stateContext);
                break;
            case HistoryType::DEEP:
                $result = $this->enterHistoryDeep($stateContext);
                break;
            default:
                throw new IllegalArgumentException("Unknown HistoryType : " . $this->historyType);
        }
        return $result;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::enterDeep()
     */
    public function enterDeep($stateContext): ImmutableState
    {
        $this->entry($stateContext);
        $lastActiveState = $this->getLastActiveChildStateOf($this, $stateContext->getStateMachineData()
            ->read());
        return $lastActiveState == null ? this : $lastActiveState->enterDeep($stateContext);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::enterShallow()
     */
    public function enterShallow($stateContext): ImmutableState
    {
        $this->entry($stateContext);
        return $this->childInitialState != null ? $this->childInitialState->enterShallow($stateContext) : $this;
    }

    /**
     * Enters this instance with history type = shallow.
     *
     * @param
     *            stateContext
     *            state context
     * @return ImmutableState the entered state
     */
    private function enterHistoryShallow($stateContext): ImmutableState
    {
        $lastActiveState = $this->getLastActiveChildStateOf($this, $stateContext->getStateMachineData()
            ->read());
        return $lastActiveState != null ? $lastActiveState->enterShallow($stateContext) : $this;
    }

    /**
     * Enters with history type = none.
     *
     * @param
     *            stateContext
     *            state context
     * @return ImmutableState the entered state.
     */
    private function enterHistoryNone($stateContext): ImmutableState
    {
        return $this->childInitialState != null ? $this->childInitialState->enterShallow($stateContext) : $this;
    }

    /**
     * Enters this instance with history type = deep.
     *
     * @param
     *            stateContext
     *            the state context.
     * @return ImmutableState the state
     */
    private function enterHistoryDeep($stateContext): ImmutableState
    {
        $lastActiveState = $this->getLastActiveChildStateOf($this, $stateContext->getStateMachineData()
            ->read());
        return $lastActiveState != null ? $lastActiveState->enterDeep($stateContext) : $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::addTransitionOn()
     */
    public function addTransitionOn($event): MutableTransition
    {
        // create transition
        $newTransition = FSM::newTransition();
        $newTransition->setSourceState($this);
        $newTransition->setEvent($event);
        // add to list of transition
        if (! array_key_exists($event, $this->transitions)) {
            $this->transitions[$event] = [];
        }
        $this->transitions[$event][] = $newTransition;

        // return the result
        return $newTransition;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::addEntryAction()
     */
    public function addEntryAction($newAction): void
    {
        array_push($this->entryActions, $newAction);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::addEntryActions()
     */
    public function addEntryActions(array $newActions): void
    {
        $this->entryActions = array_merge($this->entryActions, $newActions);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::addExitAction()
     */
    public function addExitAction($newAction): void
    {
        array_push($this->exitActions, $newAction);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::addExitActions()
     */
    public function addExitActions(array $newActions): void
    {
        $this->exitActions = array_merge($this->exitActions, $newActions);
    }

    private function isParentOf(ImmutableState $state): bool
    {
        $parent = $state->getParentState();
        while (isset($parent)) {
            if ($parent == $this) {
                return true;
            }
            $parent = $parent->getParentState();
        }
        return false;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::internalFire()
     */
    public function internalFire($stateContext): void
    {
        $currentTransitionResult = $stateContext->getResult();
        if ($this->isParallelState()) {
            /*
             * The parallelism in the State Machine framework follows an interleaved semantics.
             * All parallel operations will be executed in a single, atomic step of the event
             * processing, so no event can interrupt the parallel operations. However, events
             * will still be processed sequentially, since the machine itself is single threaded.
             *
             * The child states execute in parallel in the sense that any event that is processed
             * is processed in each child state independently, and each child state may take a different
             * transition in response to the event. (Similarly, one child state may take a transition
             * in response to an event, while another child ignores it.)
             */
            $parallelStates = $this->getSubStatesOn($this, $stateContext->getStateMachineData()
                ->read());
            foreach ($parallelStates as $parallelState) {
                if ($parallelState->isFinalState()) {
                    continue;
                }
                // context isolation as entering a new region
                $subTransitionResult = FSM::newResult(false, $parallelState, $currentTransitionResult);
                $subStateContext = FSM::newStateContext($stateContext->getStateMachine(), $stateContext->getStateMachineData(), $parallelState, $stateContext->getEvent(), $stateContext->getContext(), $subTransitionResult, $stateContext->getExecutor());
                $parallelState->internalFire($subStateContext);
                if ($subTransitionResult->isDeclined()) {
                    continue;
                }

                if (! $this->isParentOf($subTransitionResult->getTargetState())) {
                    // child state transition exit the parallel state
                    $currentTransitionResult->setTargetState($subTransitionResult->getTargetState());
                    return;
                }
                $stateContext->getStateMachineData()
                    ->write()
                    ->subStateFor($this->getStateId(), $subTransitionResult->getTargetState()
                    ->getStateId());
                if ($subTransitionResult->getTargetState()->isFinalState()) {
                    $parentState = $subTransitionResult->getTargetState()->getParentState();
                    $grandParentState = $parentState->getParentState();
                    // When all of the children reach final states, the parallel state itself is considered
                    // to be in a final state, and a completion event is generated.
                    if ($grandParentState != null && $grandParentState->isParallelState()) {
                        $allReachedFinal = true;
                        $nsubstates = $this->getSubStatesOn($grandParentState, $stateContext->getStateMachineData()
                            ->read());
                        foreach ($nsubstates as $subState) {
                            if (! $subState->isFinalState()) {
                                $allReachedFinal = false;
                                break;
                            }
                        }
                        if ($allReachedFinal) {
                            $stateMachine = $stateContext->getStateMachine();
                            $stateMachineImpl = $stateMachine;
                            $stateMachine->fireImmediate($stateMachineImpl->getFinishEvent(), $stateContext->getContext());
                            return;
                        }
                    }
                }
            }
        }

        $transitions = $this->getTransitions($stateContext->event);
        foreach ($transitions as $transition) {
            $transition->internalFire($stateContext);
            if ($currentTransitionResult->isAccepted()) {
                $targetState = $currentTransitionResult->getTargetState();
                if ($targetState->isFinalState() && ! $targetState->isRootState()) {
                    // TODO-hhe: fire event to notify listeners???
                    $parentState = $targetState->getParentState();
                    $abstractStateMachine = $stateContext->getStateMachine();
                    $finishContext = FSM::newStateContext($stateContext->getStateMachine(), $stateContext->getStateMachineData(), $parentState, $abstractStateMachine->getFinishEvent(), $stateContext->getContext(), $currentTransitionResult, $stateContext->getExecutor());
                    $parentState->internalFire($finishContext);
                    // if(!parentState.isRegion()) {
                    // currentTransitionResult.setTargetState(parentState);
                    // StateMachine<T, S, E, C> stateMachine = stateContext.getStateMachine();
                    // AbstractStateMachine<T, S, E, C> stateMachineImpl = (AbstractStateMachine<T, S, E, C>)
                    // stateContext.getStateMachine();
                    // stateMachine.fireImmediate(stateMachineImpl.getFinishEvent(), stateContext.getContext());
                    // }
                }
                return;
            }
        }

        // fire to super state
        if ($currentTransitionResult->isDeclined() && $this->getParentState() != null && ! $this->getParentState()->isRegion() && ! $this->getParentState()->isParallelState()) {
            // logger.debug("Internal notify the same event to parent state");
            $this->getParentState()->internalFire($stateContext);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::isRootState()
     */
    public function isRootState(): bool
    {
        return ! isset($this->parentState);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::isFinalState()
     */
    public function isFinalState(): bool
    {
        return $this->finalState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::setFinal()
     */
    public function setFinal(bool $isFinal): void
    {
        $this->finalState = $isFinal;
    }

    /**
     *
     * @param Visitor $visitor
     */
    public function accept(Visitor $visitor): void
    {
        $visitor->visitOnEntry($this);
        $list = $this->getAllTransitions();
        foreach ($list as $transition) {
            $transition->accept($visitor);
        }
        if ($this->childStates != null) {
            foreach ($this->childStates as $childState) {
                $childState->accept($visitor);
            }
        }
        $visitor->visitOnExit($this);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getLevel()
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::setLevel()
     */
    public function setLevel(int $level): void
    {
        $this->level = $level;
        foreach ($this->childStates as $state) {
            $state->setLevel($this->level + 1);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::addChildState()
     */
    public function addChildState(MutableState $childState): void
    {
        if (! isset($childState)) {
            return;
        }
        if (indexOf($childState, $this->childStates) < 0) {
            array_push($this->childStates, $childState);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getHistoryType()
     */
    public function getHistoryType(): string
    {
        return $this->historyType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::setHistoryType()
     */
    public function setHistoryType(string $historyType): void
    {
        $this->historyType = $historyType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getCompositeType()
     */
    public function getCompositeType(): string
    {
        return $this->compositeType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableState::setCompositeType()
     */
    public function setCompositeType(string $compositeType): void
    {
        $this->compositeType = $compositeType;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::isParallelState()
     */
    public function isParallelState(): bool
    {
        return $this->compositeType == StateCompositeType::PARALLEL;
    }

    public function __toString()
    {
        return '' . $this->stateId;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::isRegion()
     */
    public function isRegion(): bool
    {
        return $this->parentState != null && $this->parentState->isParallelState();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::verify()
     */
    public function verify(): void
    {
        if ($this->isFinalState()) {
            if ($this->isParallelState()) {
                throw new IllegalStateException("Final state cannot be parallel state.");
            } else if ($this->hasChildStates()) {
                throw new IllegalStateException("Final state cannot have child states.");
            }
        }

        // make sure that every event can only trigger one transition happen at one time
        $allTransitions = $this->getAllTransitions();
        foreach ($allTransitions as $t) {
            $t->verify();
            $conflictTransition = $this->checkConflictTransitions($t, $allTransitions);
            if ($conflictTransition != null) {
                throw new \RuntimeException("Transition " . $t . " is conflicted with " . $conflictTransition . ".");
            }
        }
    }

    public function checkConflictTransitions(ImmutableTransition $target, array $allTransitions): ?ImmutableTransition
    {
        foreach ($allTransitions as $t) {
            if ($target == $t || $t->getCondition()::class == Never::class/* Conditions::Never::class */) {
                continue;
            }
            if ($t->isMatch($target->getSourceState()
                ->getStateId(), $target->getTargetState()
                ->getStateId(), $target->getEvent(), $target->getPriority())) {
                if ($t->getCondition()->getClass() == 'Always'/* Conditions:: Always::class*/)
                    return target;
                if ($target->getCondition()->getClass() == 'Always'/* Conditions. Always::class*/)
                    return target;
                if ($t->getCondition()
                    ->name()
                    ->equals($target->getCondition()
                    ->name()))
                    return $target;
            }
        }
        return null;
    }

    private function getSubStatesOn(ImmutableState $parentState, StateMachineDataReader $read): array
    {
        $subStates = [];
        foreach ($read->subStatesOn($parentState->getStateId()) as $stateId) {
            $subStates->add($read->rawStateFrom($stateId));
        }
        return $subStates;
    }

    private function getLastActiveChildStateOf(ImmutableState $parentState, StateMachineDataReader $read): ImmutableState
    {
        $childStateId = $read->lastActiveChildStateOf($parentState->getStateId());
        if ($childStateId != null) {
            return $read->rawStateFrom($childStateId);
        } else {
            return $parentState->getInitialState();
        }
    }

    protected function getKey($stateMachine): string
    {
        return $stateMachine->getIdentifier() . '@' . $this->getPath();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::getPath()
     */
    public function getPath(): string
    {
        $currentId = $this->stateId; // RuntimeException
        if (isset($this->parentState)) {
            return $currentId;
        } else {
            return $this->parentState->getPath() . "/" . $currentId;
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableState::isChildStateOf()
     */
    public function isChildStateOf(ImmutableState $input): bool
    {
        $curr = $this;
        while ($curr->getLevel() > $input->getLevel()) {
            $curr = $curr->getParentState();
        }
        return $this != $input && $curr == $input;
    }
}

