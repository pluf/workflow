
## Declarative Annotation

A declarative way is also provided to define and also to extend the state machine. Here is an example.

```php
#[
    State(name="A", entryCallMethod="entryStateA", exitCallMethod="exitStateA"),
    State(name="B", entryCallMethod="entryStateB", exitCallMethod="exitStateB"),

    Transit(from="A", to="B", on="GoToB", callMethod="stateAToStateBOnGotoB"),
    Transit(from="A", to="A", on="WithinA", callMethod="stateAToStateAOnWithinA", type=TransitionType.INTERNAL)
]
interface MyStateMachine extends StateMachine {
    publid function entryStateA(MyState $from, MyState $to, MyEvent $event, MyContext $context);
    publid function stateAToStateBOnGotoB(MyState $from, MyState $to, MyEvent $event, MyContext $context)
    publid function stateAToStateAOnWithinA(MyState $from, MyState $to, MyEvent $event, MyContext $context)
    publid function exitStateA(MyState $from, MyState $to, MyEvent $event, MyContext $context);
    ...
}
```

The annotation can be defined in both implementation class of state machine or any interface that state 
machine will be implemented. It also can be used mixed with fluent API, which means the state 
machine defined in fluent API can also be extended by these annotations. (One thing you may need 
to be noticed, the method defined within interface must be public, which means also the method 
call action implementation will be public to caller.)