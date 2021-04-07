<?php
namespace Pluf\Workflow;

/**
 * This class will hold all the transition result including result of nested transitions.
 */
interface TransitionResult
{

    /**
     * If any transition including all nested transitions is accepted, the parent transition is
     * accepted accordingly.
     *
     * @return true if transition is accepted; false if transition result is declined
     */
    public function isAccepted(): bool;

    /**
     * If all transitions including all nested transitions is declined, the parent transition is
     * declined accordingly.
     *
     * @return false if transition is accepted; true if transition result is declined
     */
    public function isDeclined(): bool;

    /**
     * Set transition accepted or not.
     *
     * @param
     *            accepted
     * @return TransitionResult transition result
     */
    public function setAccepted(bool $accepted): TransitionResult;

    /**
     *
     * @return ImmutableState target state of transition
     */
    public function getTargetState(): ImmutableState;

    /**
     * Set target state of transition
     *
     * @param
     *            targetState
     * @return TransitionResult transition result
     */
    public function setTargetState(ImmutableState $targetState): TransitionResult;

    /**
     *
     * @return parent transition result
     */
    public function getParentResut(): TransitionResult;

    /**
     * Set parent transition result
     *
     * @param
     *            result
     * @return TransitionResult transition result
     */
    public function setParent(TransitionResult $result): TransitionResult;

    /**
     *
     * @return array nested transition result of current transition
     */
    public function getSubResults(): array;

    /**
     *
     * @return array all the accepted transition result of current transition
     */
    public function getAcceptedResults(): array;
}

