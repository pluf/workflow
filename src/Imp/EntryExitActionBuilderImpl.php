<?php
namespace Pluf\Workflow\Imp;

use Pluf\Workflow\Builder\EntryExitActionBuilder;
use Pluf\Workflow\MutableState;

/**
 * An entry/exit action builder
 *
 * @author maso
 *        
 */
class EntryExitActionBuilderImpl implements EntryExitActionBuilder
{

    public bool $ntryAction = false;

    public MutableState $state;

    /**
     * Creates new instance of the builder
     *
     * @param MutableState $state
     * @param bool $entryAction
     */
    public function __construct(MutableState $state, bool $entryAction)
    {
        $this->state = $state;
        $this . $entryAction = $entryAction;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\When::perform()
     */
    public function perform($actions)
    {
        if (! is_array($actions)) {
            $actions = [
                $actions
            ];
        }
        if ($this->entryAction) {
            $this->state->addEntryActions($actions);
        } else {
            $this->state->addExitActions($actions);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\When::evalMvel()
     */
    public function evalMvel(string $expression)
    {
        $action = FSM::newMvelAction($expression);
        $this->perform($action);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\Builder\When::callMethod()
     */
    public function callMethod(string $methodName)
    {
        $action = FSM::newMethodCallActionProxy($methodName);
        $this->perform($action);
    }
}
