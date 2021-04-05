<?php
namespace Pluf\Workflow;

/**
 * <p><b>State</b> The basic unit that composes a state machine.
 * A state machine can be in one state at
 * any particular time.</p>
 * <p><b>Entry Action</b> An activity executed when entering the state</p>
 * <p><b>Entry Action</b> An activity executed when entering the state</p>
 * <p><b>Final State</b> A state which represents the completion of the state machine.</p>
 */
interface ImmutableState
{

    /**
     *
     * @return mixed state id
     */
    function getStateId();

    /**
     *
     * @return bool whether state is root state
     */
    function isRootState(): bool;

    /**
     *
     * @return array Activities executed when entering the state
     */
    function getEntryActions(): array;

    /**
     *
     * @return array Activities executed when exiting the state
     */
    function getExitActions(): array;

    /**
     *
     * @return array All transitions start from this state
     */
    function getAllTransitions(): array;

    /**
     *
     * @param
     *            event
     * @return array Transitions triggered by event
     */
    function getTransitions($event): array;

    /**
     *
     * @return array a map of events that can be accepted by this state
     */
    function getAcceptableEvents(): array;

    /**
     * Entry state with state context
     *
     * @param
     *            stateContext
     */
    function entry(StateContext $stateContext): void;

    /**
     * Enters this state by its history depending on its
     * <code>HistoryType</code>.
     * The <code>Entry</code> method has to be called
     * already.
     *
     * @param
     *            stateContext
     *            the state context.
     * @return ImmutableState the active state. (depends on this states<code>HistoryType</code>)
     */
    function enterByHistory(StateContext  $stateContext): ImmutableState;

    /**
     * Enters this state is deep mode: mode if there is one.
     *
     * @param
     *            stateContext
     *            the event context.
     * @return ImmutableState the active state.
     */
    function enterDeep(StateContext $stateContext): ImmutableState;

    /**
     * Enters this state is shallow mode: The entry action is executed and the
     * initial state is entered in shallow mode if there is one.
     *
     * @param
     *            stateContext
     * @return ImmutableState child state entered by shadow
     */
    function enterShallow(StateContext $stateContext): ImmutableState;

    /**
     * Exit state with state context
     *
     * @param
     *            stateContext
     */
    function exit(StateContext $stateContext): void;

    /**
     *
     * @return parent state
     */
    function getParentState(): ?ImmutableState;

    /**
     *
     * @return array child states
     */
    function getChildStates(): array;

    /**
     *
     * @return bool whether state has child states
     */
    function hasChildStates(): bool;

    /**
     *
     * @return ImmutableState initial child state
     */
    function getInitialState(): ImmutableState;

    /**
     * Notify transitions when receiving event.
     *
     * @param
     *            stateContext
     */
    function internalFire(StateContext $stateContext): void;

    /**
     *
     * @return bool whether current state is final state
     */
    function isFinalState(): bool;

    /**
     *
     * @return int hierarchy state level
     */
    function getLevel(): int;

    /**
     *
     * @return HistoryType Historical type of state
     */
    function getHistoryType()/* : HistoryType */;

    /**
     * See StateCompositeType
     *
     * @return string child states composite type
     */
    function getCompositeType(): string/* : StateCompositeType */;

    /**
     *
     * @return bool whether child states composite type is parallel
     */
    function isParallelState(): bool;

    function isRegion(): bool;

    /**
     * Verify state correctness
     */
    function verify(): void;

    function getPath(): string;

    function isChildStateOf(ImmutableState $input): bool;
}

