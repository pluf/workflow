<?php
namespace Pluf\Tests\Beep;

use Pluf\Workflow\Attributes\State;
use Pluf\Workflow\Attributes\Transit;

#[State(name: 'ready')]
#[Transit(from: 'ready', to: 'ready', on: 'trigger', callMethod: 'beep')]
class Beeper
{

    public int $count = 0;

    public function beep(int $count = 0)
    {
        $this->count += $count;
    }
}

