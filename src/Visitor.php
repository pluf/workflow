<?php
namespace Pluf\Workflow;

interface Visitor
{

    /**
     *
     * @param
     *            visitable the element to be visited.
     */
    public function visitOnEntry(StateMachine $visitable);

    /**
     *
     * @param
     *            visitable the element to be visited.
     */
    public function visitOnExit(StateMachine $visitable);

    /**
     *
     * @param
     *           ImmutableState|ImmutableTransition visitable the element to be visited.
     */
    public function visitOnEntry(ImmutableState|ImmutableTransition $visitable);

    /**
     *
     * @param
     *          ImmutableState|ImmutableTransition  visitable the element to be visited.
     */
    public function visitOnExit(ImmutableState|ImmutableTransition $visitable);

}

