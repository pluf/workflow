# Transition Exception Handling

When exception happened during state transition, the executed action list will be 
aborted and state machine will be enter error status, which means the state machine 
instance cannot process event anymore. If user continue to fire event to the state 
machine instance, a IllegalStateException will be thrown out.

All the exception happened during transition phase including action execution and 
external listener invocation will be wrapped into TransitionException(unchecked 
exception). Currently, the default exception handling strategy is simple and 
rude by just continuing throw out the exception, see AbstractStateMachine::afterTransitionCausedException method.

```php
protected function afterTransitionCausedException(...) { throw e; }
```

If state machine can be recovered from this exception, user can extend 
afterTransitionCausedException method, and add corresponding the recovery logic 
in this method. **DONOT** forget to set state machine status back to normal at the end. e.g.

```php
protected function afterTransitionCausedException($from, $to, $event, $context) {
    $targeException = $this->getLastException()
    	->getTargetException();
    // recover from IllegalArgumentException thrown out from state 'A' to 'B' caused by event 'ToB'
    if($targeException instanceof IllegalArgumentException &&
            $from === "A" && 
            $to === "B" && 
            $event === "ToB") {
        // do some error clean up job here
        // ...
        // after recovered from this exception, reset the state machine status back to normal
        $this->setStatus(StateMachineStatus::IDLE);
    } else if(...) {
        // recover from other exception ...
    } else {
        parent::afterTransitionCausedException($from, $to, $event, $context);
    }
}
```