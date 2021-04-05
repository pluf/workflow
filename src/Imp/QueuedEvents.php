<?php
namespace Pluf\Workflow\Imp;

class QueuedEvents
{

    private $evetns = [];

    public function poll()
    {
        return $this->pop();
    }

    public function pop()
    {
        return array_pop($this->evetns);
    }

    public function push($event): self
    {
        array_push($event);
        return $this;
    }

    public function addFirst($event): self
    {
        return $this->push($event);
    }

    public function add($event): self
    {
        array_unshift($this->evetns, $event);
        return $this;
    }

    public function remove()
    {
        return array_pop($this->evetns);
    }

    public function addLast($event): self
    {
        return $this->add($event);
    }

    public function clear(): self
    {
        $this->evetns = [];
        return $this;
    }
}

