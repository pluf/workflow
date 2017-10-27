<?php

/**
 * Workflow event
 * 
 * When an transaction is run in from a workflow machine, an event pass into
 * the listeners. This class describe workflow even.
 * 
 * @author maso<mostafa.barmshory@dpq.co.ir>
 *
 */
class Workflow_Event
{
    /**
     * Start state
     * @var array
     */
    var $from;

    /**
     * To state
     * @var array
     */
    var $to;

    /**
     * Transaction
     * @var string
     */
    var $event;

    /**
     * Source of event
     * @var Pluf_Model
     */
    var $object;

    /**
     * System request
     * @var Pluf_HTTP_Request
     */
    var $request;

    /**
     * Machine
     * @var Workflow_Machine
     */
    var $source;

    public function __construct ($request, $object, $action, $state, 
            $transaction)
    {
        $this->request = $request;
        $this->object = $object;
        $this->event = $action;
        $this->from = $state['name'];
        $this->to = $transaction['next'];
    }
}