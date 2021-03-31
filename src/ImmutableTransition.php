<?php
namespace Pluf\Workflow;

/**
 * <p><b>Transition</b> A directed relationship between two states which represents the complete response
 * of a state machine to an occurrence of an event of a particular type.</p>
 *
 * <p><b>Condition</b> A constraint which must evaluate to true after the trigger occurs in order for the
 * transition to complete.</p>
 *
 * <p><b>Transition Action</b> An activity which is executed when performing a certain transition.</p>
 *
 * <p><b>Trigger(Event)</b> A triggering activity that causes a transition to occur.</p>
 */
interface ImmutableTransition
{

    /**
     *
     * @return ImmutableState Transition source state
     */
    function getSourceState(): ImmutableState;

    /**
     *
     * @return ImmutableState Transition destination state
     */
    function getTargetState(): ImmutableState;

    /**
     *
     * @return ImmutableState Transition action list
     */
    function getActions(): array;

    /**
     * Execute transition under state context
     *
     * @param
     *            stateContext
     * @return ImmutableState state when transition finished
     */
    function transit(StateContext $stateContext): ImmutableState;

    /**
     *
     * @return Condition of the transition
     */
    function getCondition(): Condition;

    /**
     *
     * @return mixed Event that can trigger the transition
     */
    function getEvent();

    /**
     *
     * @return string type of transition
     */
    function getType(): string;

    function getPriority(): int;

    // function isMatch(S fromState, S toState, E event, int priority): bool;
    function isMatch($fromState, $toState, $event, int $priority, ?string $condClazz = null, ?string $type = null): bool;

    /**
     * Notify transition when receiving event
     *
     * @param
     *            stateContext
     */
    function internalFire(StateContext $stateContext): void;

    /**
     * Verify transition correctness
     */
    function verify(): void;
}

