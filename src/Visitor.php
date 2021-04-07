<?php
namespace Pluf\Workflow;

interface Visitor
{

    /**
     *
     * @param
     *            visitable the element to be visited.
     */
    public function visitOnEntry($visitable);

    /**
     *
     * @param
     *            visitable the element to be visited.
     */
    public function visitOnExit($visitable);
}

