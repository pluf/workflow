<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\MutableTransition;
use Pluf\Workflow\ImmutableState;
use Pluf\Workflow\TransitionType;
use Pluf\Workflow\StateContext;
use Pluf\Workflow\Condition;
use Pluf\Workflow\Conditions;
use Pluf\Workflow\Visitor;

class TransitionImpl implements MutableTransition
{

    public ImmutableState $sourceState;

    public ImmutableState $targetState;

    public $event;

    public array $actions = [];

    public ?Condition $condition = null;

    public string $type = TransitionType::EXTERNAL;

    public int $priority;

    /**
     * Creates new instance of transition
     */
    public function __construct()
    {
        $this->condition = Conditions::always();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::getSourceState()
     */
    public function getSourceState(): ImmutableState
    {
        return $this->sourceState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::getTargetState()
     */
    public function getTargetState(): ImmutableState
    {
        return $this->targetState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::getActions()
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::transit()
     */
    public function transit(StateContext $stateContext): ImmutableState
    {
        $stateContext->getExecutor()->begin("TRANSITION__" . $this);
        foreach ($this->actions as $action) {
            $stateContext->getExecutor()->defer($action, $this->sourceState->getStateId(), $this->targetState->getStateId(), $stateContext->getEvent(), $stateContext->getContext(), $stateContext->getStateMachine());
        }
        return $this->targetState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableTransition::setSourceState()
     */
    public function setSourceState(ImmutableState $state): void
    {
        $this->sourceState = $state;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableTransition::setTargetState()
     */
    public function setTargetState(ImmutableState $state): void
    {
        $this->targetState = $state;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableTransition::addAction()
     */
    public function addAction($newAction): void
    {
        array_push($this->actions, $newAction);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableTransition::addActions()
     */
    public function addActions(array $newActions): void
    {
        $this->actions = array_merge($this->actions, $newActions);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::getCondition()
     */
    public function getCondition(): Condition
    {
        return $this->condition;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableTransition::setCondition()
     */
    public function setCondition(Condition $condition): void
    {
        $this->condition = $condition;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::getEvent()
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableTransition::setEvent()
     */
    public function setEvent($event): void
    {
        $this->event = $event;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::getType()
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     *
     * @see TransitionType
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::getPriority()
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\MutableTransition::setPriority()
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    private function doTransit(ImmutableState $source, ImmutableState $target, StateContext $stateContext): void
    {
        if ($target->isChildStateOf($source) && $this->type == TransitionType::EXTERNAL) {
            // exit and re-enter current state for external transition to child state
            $source->exit($stateContext);
            $source->entry($stateContext);
        }
        $this->doTransitInternal($source, $target, $stateContext);
    }

    /**
     * Recursively traverses the state hierarchy, exiting states along the way, performing the action, and entering states to the target.
     * <hr>
     * There exist the following transition scenarios:
     * <ul>
     * <li>0. there is no target state (internal transition) --> handled outside this method.</li>
     * <li>1. The source and target state are the same (self transition) --> perform the transition directly: Exit source state, perform
     * transition actions and enter target state</li>
     * <li>2. The target state is a direct or indirect sub-state of the source state --> perform the transition actions, then traverse the
     * hierarchy from the source state down to the target state, entering each state along the way. No state is exited.
     * <li>3. The source state is a sub-state of the target state --> traverse the hierarchy from the source up to the target, exiting each
     * state along the way. Then perform transition actions. Finally enter the target state.</li>
     * <li>4. The source and target state share the same super-state</li>
     * <li>5. All other scenarios:
     * <ul>
     * <li>a. The source and target states reside at the same level in the hierarchy but do not share the same direct super-state</li>
     * <li>b. The source state is lower in the hierarchy than the target state</li>
     * <li>c. The target state is lower in the hierarchy than the source state</li>
     * </ul>
     * </ul>
     *
     * @param
     *            source the source state
     * @param
     *            target the target state
     * @param
     *            stateContext the state context
     */
    private function doTransitInternal(ImmutableState $source, ImmutableState $target, StateContext $stateContext): void
    {
        if ($source == $this->getTargetState()) {
            // Handles 1.
            // Handles 3. after traversing from the source to the target.
            if ($this->type == TransitionType::LOCAL) {
                // not exit and re-enter the composite (source) state for
                // local transition
                $this->transit($stateContext);
            } else {
                $source->exit($stateContext);
                $this->transit($stateContext);
                $this->getTargetState()->entry($stateContext);
            }
        } else if ($source == $target) {
            // Handles 2. after traversing from the target to the source.
            $this->transit($stateContext);
        } else if ($source->getParentState() == $target->getParentState()) {
            // Handles 4.
            // Handles 5a. after traversing the hierarchy until a common ancestor if found.
            $source->exit($stateContext);
            $this->transit($stateContext);
            $this->target->entry($stateContext);
        } else {
            // traverses the hierarchy until one of the above scenarios is met.
            if ($source->getLevel() > $target . getLevel()) {
                // Handles 3.
                // Handles 5b.
                $source->exit($stateContext);
                $this->doTransitInternal($source->getParentState(), $target, $stateContext);
            } else if ($source->getLevel() < $target->getLevel()) {
                // Handles 2.
                // Handles 5c.
                $this->doTransitInternal($source, $target->getParentState(), $stateContext);
                $target->entry($stateContext);
            } else {
                // Handles 5a.
                $source->exit($stateContext);
                $this->doTransitInternal($source->getParentState(), $target->getParentState(), $stateContext);
                $target->entry($stateContext);
            }
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::internalFire()
     */
    public function internalFire(StateContext $stateContext): void
    {
        // Fix issue17
        if ($this->type == TransitionType::INTERNAL && $stateContext->getSourceState()->getStateId() != $this->targetState->getStateId()) {
            return;
        }
        if ($this->condition->isSatisfied($stateContext->getContext())) {
            $newState = $stateContext->getSourceState();
            if ($this->type == TransitionType::INTERNAL) {
                $newState = $this->transit($stateContext);
            } else {
                // exit origin states
                $this->unwindSubStates($stateContext->getSourceState(), $stateContext);
                // perform transition actions
                $this->doTransit($this->getSourceState(), $this->getTargetState(), $stateContext);
                // enter new states
                $newState = $this->getTargetState()->enterByHistory($stateContext);
            }
            $stateContext->getResult()
                ->setAccepted(true)
                ->setTargetState($newState);
        }
    }

    private function unwindSubStates(ImmutableState $orgState, StateContext $stateContext): void
    {
        for ($state = $orgState; $state != $this->getSourceState(); $state = $state->getParentState()) {
            if ($state != null) {
                $state->exit($stateContext);
            }
        }
    }

    public function accept(Visitor $visitor): void
    {
        $visitor->visitOnEntry($this);
        $visitor->visitOnExit($this);
    }

    // public function isMatch($romState, $toState, $event, int $priority) : bool{
    // if(toState==null && !getTargetState().isFinalState())
    // return false;
    // if(toState!=null && !getTargetState().isFinalState() &&
    // !getTargetState().getStateId().equals(toState))
    // return false;
    // if(!getEvent().equals(event))
    // return false;
    // if(getPriority()!=priority)
    // return false;
    // return true;
    // }
    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::isMatch()
     */
    public function isMatch($fromState, $toState, $event, int $priority, ?string $condClazz = null, ?string $type = null): bool
    {
        $flag = true;
        if ($toState == null && ! $this->getTargetState()->isFinalState())
            $flag = false;
        if ($toState != null && ! $this->getTargetState()->isFinalState() && $this->getTargetState()->getStateId() !== $toState)
            $flag = false;
        if ($this->getEvent() !== $event)
            $flag = false;
        if ($this->getPriority() != $priority)
            $flag = false;

        if (! isset($condClazz)) {
            return $flag;
        }

        if (! $flag)
            return false;
        if ($this->getCondition() != $condClazz)
            return false;
        if (! $this->getType() !== $type)
            return false;
        return true;
    }

    public function __toString()
    {
        return $this->sourceState . "-[" . $this->event . ", " . $this->condition . name() . ", " . $this->priority . ", " . $this->type . "]->" . $this->targetState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ImmutableTransition::verify()
     */
    public function verify(): void
    {
        if ($this->type == TransitionType::INTERNAL && $this->sourceState != $this->targetState) {
            throw new \RuntimeException("Internal transition source state " . $this->sourceState . " " + "and target state " . $this->targetState . " must be same.");
        }
    }
}

