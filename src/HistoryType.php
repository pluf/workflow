<?php
namespace Pluf\Workflow;

/**
 * Defines the history behavior of a state (on re-entrance of a super state).
 *
 * @author Henry.He
 *        
 */
class HistoryType
{

    /**
     * The state enters into its initial sub-state.
     * The sub-state itself enters its initial sub-state and so on until the innermost nested
     * state is reached. This is the default.
     */
    public const NONE = 'NONE';

    /**
     * The state enters into its last active sub-state.
     * The sub-state itself enters its initial sub-state and so on until the innermost
     * nested state is reached.
     */
    public const SHALLOW = 'SHALLOW';

    /**
     * The state enters into its last active sub-state.
     * The sub-state itself enters into-its last active state and so on until the innermost
     * nested state is reached.
     */
    public const DEEP = 'DEEP';
}

