<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\ImmutableState;
use Pluf\Workflow\TransitionResult;

class TransitionResultImpl implements TransitionResult
{

    private bool $accepted = false;

    private ImmutableState $targetState;

    private ?TransitionResult $parent;

    private array $subResults = [];
    
    public function __construct(bool $accepted, ImmutableState $targetState, ?TransitionResult $parent = null) {
        $this->accepted = $accepted;
        $this->targetState = $targetState;
        $this->parent = $parent;
    }

    private function addSubResult(TransitionResult $subResult): void
    {
        array_push($this->subResults, $subResult);
    }

    private function getRootResult(): TransitionResult
    {
        if ($this->parent == null)
            return $this;
        return $this->parent->getRootResult();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\TransitionResult::isAccepted()
     */
    public function isAccepted(): bool
    {
        if ($this->accepted) {
            return true;
        }
        $subResults = $this->getSubResults();
        foreach ($subResults as $subResult) {
            if ($subResult->isAccepted()) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\TransitionResult::getTargetState()
     */
    public function getTargetState(): ImmutableState
    {
        return $this->targetState;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\TransitionResult::getSubResults()
     */
    public function getSubResults(): array
    {
        return array_values($this->subResults);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\TransitionResult::getAcceptedResults()
     */
    public function getAcceptedResults(): array
    {
        $acceptedResults = [];
        foreach ($this->subResults as $subResult) {
            $acceptedResults = array_merge($acceptedResults, $subResult->getAcceptedResults());
        }
        if ($this->accepted) {
            $acceptedResults[] = $this;
        }
        return $acceptedResults;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\TransitionResult::getParentResut()
     */
    public function getParentResut(): TransitionResult
    {
        return $this->parent;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\TransitionResult::setAccepted()
     */
    public function setAccepted(bool $accepted): TransitionResult
    {
        $this->accepted = $accepted;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\TransitionResult::setTargetState()
     */
    public function setTargetState(ImmutableState $targetState): TransitionResult
    {
        $this->targetState = $targetState;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\TransitionResult::setParent()
     */
    public function setParent(TransitionResult $parent): TransitionResult
    {
        $this->parent = $parent;
        if ($this->parent instanceof TransitionResultImpl) {
            $parent->addSubResult($this);
        }
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\TransitionResult::isDeclined()
     */
    public function isDeclined(): bool
    {
        return ! $this->isAccepted();
    }
}

