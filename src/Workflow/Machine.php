<?php

/*
 * This file is part of Pluf Framework, a simple PHP Application Framework.
 * Copyright (C) 2010-2020 Phoinex Scholars Co. (http://dpq.co.ir)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// XXX: maso, handle otherwise states
// XXX: maso, handle undefined state
// TODO: maos, 2017: $request are store in global vars. remove from params
/**
 * State machine system.
 *
 * The Workflow component provides tools for managing a workflow or finite state
 * machine.
 */
class Workflow_Machine
{

    const KEY_ACTION = 'action';

    const STATE_OTHERS = '*';

    const STATE_UNDEFINED = '#';

    var $states = null;

    /**
     * Name of state property
     *
     * This is a field name where state is stored in.
     *
     * @var string
     */
    var $statePropertyName = null;

    var $signals = array();
    
    /**
     * Perform action on object
     *
     * @deprecated use Workflow_Machine::apply($object, $action)
     * @param unknown $request            
     * @param unknown $object            
     * @param unknown $action            
     * @throws Pluf_Exception
     * @return Workflow_Machine
     */
    public function transact ($request, $object, $action)
    {
        return $this->apply($object, $action);
    }

    /**
     * Send signals
     *
     * @param Pluf_Model $object            
     * @param string $action            
     * @param unknown $state            
     * @param unknown $transaction            
     */
    private function sendSignals ($object, $action, $state, $transaction)
    {
        if (! isset($this->signals) && ! is_array($this->signals)) {
            return;
        }
        $event = new Workflow_Event($GLOBALS['_PX_request'], $object, $action, 
                $state, $transaction);
        foreach ($this->signals as $signal) {
            Pluf_Signal::send($signal, 'Workflow_Machine', $event);
        }
    }

    /**
     * Applies action on the object
     *
     * @param Pluf_Model $object            
     * @param string $action            
     * @return Workflow_Machine
     */
    public function apply ($object, $action)
    {
        $stateName = $object->state;
        if (empty($stateName)) {
            $stateName = Workflow_Machine::STATE_UNDEFINED;
            $state = null;
            if (array_key_exists(Workflow_Machine::STATE_UNDEFINED, 
                    $this->states)) {
                $transaction = $this->states[Workflow_Machine::STATE_UNDEFINED];
            } else {
                throw new Pluf_Exception(sprintf("Unknown state!", $stateName));
            }
        } else {
            $state = $this->getState($object);
            $state['name'] = $object->state;
            $transaction = $this->getTransaction($state, $action);
        }
        $this->checkPreconditions($object, $action, $transaction);
        // Run the transaction
        if (array_key_exists(Workflow_Machine::KEY_ACTION, $transaction)) {
            return call_user_func_array($transaction[Workflow_Machine::KEY_ACTION], 
                    array(
                            $GLOBALS['_PX_request'],
                            $object,
                            $action
                    ));
        }
        // Update state
        $object->state = $transaction['next'];
        $object->update();
        
        // Send signals
        $this->sendSignals($object, $action, $state, $transaction);
        return $this;
    }

    /**
     * Check if it is possible to perform action
     *
     * @param Pluf_Model $object            
     * @param string $action            
     * @return boolean true if it is possible to apply action.
     */
    public function can ($object, $action)
    {
        return false;
    }

    /*
     * Gets state
     */
    private function getState ($object)
    {
        $stateName = $object->state;
        // check state
        if (! array_key_exists($stateName, $this->states)) {
            // throw invalid state
            throw new Pluf_Exception(
                    sprintf("State not found(name:%s)", $stateName));
        }
        return $this->states[$stateName];
    }

    private function getTransaction ($state, $action)
    {
        // check action
        if (! array_key_exists($action, $state)) {
            // throw invalid transaction
            throw new Pluf_Exception(
                    sprintf("transaction not found (State:%s, Action:%s)", 
                            $state['name'], $action));
        }
        return $state[$action];
    }

    private function checkPreconditions ($object, $action, $transaction)
    {
        // check all preconditions
        $preconds = array();
        if (array_key_exists('preconditions', $transaction)) {
            $precond = $transaction['preconditions'];
        }
        foreach ($preconds as $precond) {
            call_user_func_array(explode('::', $precond), 
                    array(
                            $GLOBALS['_PX_request'],
                            $object,
                            $action
                    ));
        }
    }

    public function setStates ($states)
    {
        $this->states = $states;
        return $this;
    }

    public function setSignals ($signals)
    {
        $this->signals = $signals;
        return $this;
    }

    public function setInitialState ($initialState)
    {
        $this->initialState = $initialState;
        return $this;
    }

    public function setProperty ($statePropertyName)
    {
        $this->statePropertyName = $statePropertyName;
        return $this;
    }
}
