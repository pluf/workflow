<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\MutableState;
use Pluf\Workflow\MutableTransition;
use Pluf\Workflow\Builder\ExternalTransitionBuilder;
use Pluf\Workflow\Builder\From;
use Pluf\Workflow\Builder\InternalTransitionBuilder;
use Pluf\Workflow\Builder\LocalTransitionBuilder;
use Pluf\Workflow\Builder\On;
use Pluf\Workflow\Builder\To;
use Pluf\Workflow\Builder\When;

class TransitionBuilderImpl implements ExternalTransitionBuilder, InternalTransitionBuilder, LocalTransitionBuilder, From, To, On
{

    public $states = null;

    private ?MutableState $sourceState;

    private ?MutableState $targetState;

    private ?MutableTransition $transition;

    private string $transitionType;

    // see TransitionType INTERNAL, ..
    private int $priority = 0;

    /**
     * Crates new instance of the transition builder
     *
     * @param array $states
     *            of the transition
     * @param string $transitionType
     *            transition type
     * @param int $priority
     *            of the transition
     */
    public function __construct($states, string $transitionType, int $priority = 0)
    {
        $this->states = $states;
        $this->transitionType = $transitionType;
        $this->priority = $priority;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\When::perform()
     */
    public function perform($action): void
    {
        if (is_array($action)) {
            $this->transition->addActions($action);
        } else {
            $this->transition->addAction($action);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\When::evalMvel()
     */
    public function evalMvel(string $expression): void
    {
        $action = FSM::newMvelAction($expression);
        $this->transition->addAction($action);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\When::callMethod()
     */
    public function callMethod(String $methodName): void
    {
        $action = FSM::newMethodCallActionProxy($methodName);
        $this->transition->addAction($action);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\To::on()
     */
    public function on($event): On
    {
        $this->transition = $this->sourceState->addTransitionOn($event);
        $this->transition->setTargetState($this->targetState);
        $this->transition->setType($this->transitionType);
        $this->transition->setPriority($this->priority);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\From::to()
     */
    public function to($stateId): To
    {
        $this->targetState = FSM::getState($this->states, $stateId);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\From::toFinal()
     */
    public function toFinal($stateId): To
    {
        $this->targetState = FSM::getState($this->states, $stateId);
        if (! $this->targetState->isFinalState()) {
            $this->targetState->setFinal(true);
        }
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\ExternalTransitionBuilder::from()
     */
    public function from($stateId): From
    {
        $this->sourceState = FSM::getState($this->states, $stateId);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\On::when()
     */
    public function when($condition): When
    {
        $this->transition->setCondition($condition);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\On::whenMvel()
     */
    public function whenMvel(string $expression): When
    {
        $cond = FSM::newMvelCondition($expression);
        $this->transition->setCondition($cond);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\InternalTransitionBuilder::within()
     */
    public function within($stateId): To
    {
        $this->sourceState = $this->targetState = FSM::getState($this->states, $stateId);
        return $this;
    }
}