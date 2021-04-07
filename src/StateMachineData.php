<?php
namespace Pluf\Workflow;

interface StateMachineData
{

    /**
     * Dump source state machine data (expect transient data, such as states)
     * into current state machine data
     *
     * @param
     *            src
     *            source state machine data
     */
    function dump(StateMachineDataReader $src): void;

    /**
     *
     * @return StateMachineDataReader state machine data reader
     */
    function read(): StateMachineDataReader;

    /**
     *
     * @return StateMachineDataWriter state machine data writer
     */
    function write(): StateMachineDataWriter;
}

