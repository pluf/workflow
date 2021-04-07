# Untyped State Machine

In order to simplify state machine usage, which may make code hard to read in some case, but 
still keep important part of type safety feature on transition action execution, 
UntypedStateMachine was implemented for this purpose.

```php
#[
	Transit(from="A", to="B", on="toB", callMethod="fromAToB"),
	Transit(from="B", to="C", on="toC"),
	Transit(from="C", to="D", on="toD"),
	
	StateMachineParameters(
		stateType:'string', 
		eventType:'string',
		contextType: 'int'
	)
]
class UntypedStateMachineSample {
    
    function fromAToB($from, $to, $event, $context): void {
        // transition action still type safe ...
    }

    function transitFromDToAOntoA($from, $to, $event, $context) {
        // transition action still type safe ...
    }
}

$builder = StateMachineBuilderFactory::create(UntypedStateMachineSample::class);
// state machine builder not type safe anymore
builder
	->externalTransition()
	->from("D")
	->to("A")
	->on('toA');
$fsm = builder->newStateMachine("A");
```

To build an UntypedStateMachine, user need to create an UntypedStateMachineBuilder through 
StateMachineBuilderFactory first. StateMachineBuilderFactory takes only one parameter which is 
type of state machine class to create UntypedStateMachineBuilder. *StateMachineParameters* is used 
to declare state machine parameter types. *AbstractUntypedStateMachine* is the base class 
of any untyped state machine.


