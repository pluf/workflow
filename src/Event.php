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
namespace Pluf\Workflow;

use Pluf_Model;
use Pluf_HTTP_Request;

/**
 * Workflow event
 *
 * When an transaction is run in from a workflow machine, an event pass into
 * the listeners. This class describe workflow even.
 *
 * @author maso<mostafa.barmshory@dpq.co.ir>
 *        
 */
class Event
{

    /**
     * Start state
     *
     * @var array
     */
    var $from;

    /**
     * To state
     *
     * @var array
     */
    var $to;

    /**
     * Transaction
     *
     * @var string
     */
    var $event;

    /**
     * Source of event
     *
     * @var Pluf_Model
     */
    var $object;

    /**
     * System request
     *
     * @var Pluf_HTTP_Request
     */
    var $request;

    /**
     * Machine
     *
     * @var Machine
     */
    var $source;

    public function __construct(?Pluf_HTTP_Request $request, ?Pluf_Model $object, $action, ?array $state = [], ?array $transaction = [])
    {
        $this->request = $request;
        $this->object = $object;
        $this->event = $action;

        if (isset($state)) {
            $this->from = $state['name'];
        }

        if (isset($transaction)) {
            $this->to = $transaction['next'];
        }
    }
}