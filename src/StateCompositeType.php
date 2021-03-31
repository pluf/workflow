<?php
namespace Pluf\Workflow;

/**
 * Composite state is defined as state that has substates (nested states). Substates could be sequential (disjoint)
 * or parallel (orthogonal). {@code StateCompositeType} defines the type of composite state.
 *
 * @author Henry.He
 */
class StateCompositeType
{

    /**
     * The child states are mutually exclusive and an initial state must
     * be set by calling MutableState.setInitialState()
     */
    public const SEQUENTIAL = 'SEQUENTIAL';

    /**
     * The child states are parallel.
     * When the parent state is entered,
     * all its child states are entered in parallel.
     */
    public const PARALLEL = 'PARALLEL';
}

