# Context Insensitive State Machine

Sometimes state transition does not care about context, which means transition mostly 
only determined by event. For this case user can use context insensitive state 
machine to simplify method call parameters.

To declare context insensitive state machine is quite simple. User only need to 
add annotation *#[ContextInsensitive]* on state machine implementation class. 
After that, context parameter can be ignored on the transition method parameter list. e.g.

```php
#[ContextInsensitive]
class ATMStateMachine {
}
```