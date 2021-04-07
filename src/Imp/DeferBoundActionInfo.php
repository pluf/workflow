<?php
namespace Pluf\Workflow\Imp;

class DeferBoundActionInfo
{

    private array $actions = [];

    private $from;

    private $to;

    private $event;

    public function __construct($from, $to, $event)
    {
        $this->from = $from;
        $this->to = $to;
        $this->event = $event;
    }

    public function isFromStateMatch($from): bool
    {
        return $this->from == null || $this->from->equals($from);
    }

    public function isToStateMatch($to): bool
    {
        return $this->to == null || $this->to->equals($to);
    }

    public function isEventStateMatch($event): bool
    {
        return $this->event == null || $this->event->equals($event);
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }
}

