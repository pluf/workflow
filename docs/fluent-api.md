# Fluent API

After state machine builder was created, we can use fluent API to define state/transition/action 
of the state machine.

```php
builder
	->externalTransition()
		->from(MyState::A)
		->to(MyState::B)
		->on(MyEvent::GoToB);
```

An **external transition** is built between state 'A' to state 'B' and triggered on received 
event 'GoToB'.

```php
builder
	->internalTransition(TransitionPriority::HIGH)
		->within(MyState::A)
		->on(MyEvent::WithinA)
		->perform(myAction);
```

An **internal transition** with priority set to high is build inside state 'A' on event 'WithinA' 
perform 'myAction'. The internal transition means after transition complete, no state is exited 
or entered. The transition priority is used to override original transition when state machine 
extended.

```php
builder
	->externalTransition()
		->from(MyState::C)
		->to(MyState::D)
		->on(MyEvent::GoToD)
		->when(new MyCondition())
		->callMethod("myInternalTransitionCall");
```

An **conditional transition** is built from state 'C' to state 'D' on event 'GoToD' when external 
context satisfied the condition restriction, then call action method "myInternalTransitionCall". 
User can also use MVEL(a powerful expression language) to describe condition in the 
following way.

```php
builder
	->externalTransition()
		->from(MyState::C)
		->to(MyState::D)
		->on(MyEvent::GoToD)
		->whenMvel("MyCondition:::(context!=null && context->getValue()>80)")
		->callMethod("myInternalTransitionCall");
```

**Note:** Characters ':::' use to separate condition name and condition expression. The 'context' is 
the predefined variable point to current Context object.

```php
builder
	->onEntry(MyState::A)
	->perform([action1, action2])
```

A list of state entry actions is defined in above sample code.

### Method Call Action

User can define anonymous actions during define transitions or state entry/exit. However, the action code 
will be scattered over many places which may make code hard to maintain. Moreover, other user cannot 
override the actions. So squirrel-foundation also support to define state machine method call action 
which comes along with state machine class itself.

```php
$builder = StateMachineBuilderFactory::create(MyStateMachine::class, MyState::class, MyEvent::class, MyContext::class);
builder
	->externalTransition()
		->from(A)
		->to(B)
		->on(toB)
		->callMethod("fromAToB");

// All transition action method stays with state machine class
class MyStateMachine extends AbstractStateMachine {
    function fromAToB(MyState $from, MyState $to, MyEvent $event, MyContext $context) {
        // this method will be called during transition from "A" to "B" on event "toB"
        // the action method parameters types and order should match
        ...
    }
}
```
    
Moreover, foundation also support define method call actions in a **Convention Over Configuration** 
manner. Basically, this means that if the method declared in state machine satisfied naming and parameters 
convention, it will be added into the transition action list and also be invoked at certain phase. e.g.

```php
function transitFromAToBOnGoToB(MyState $from, MyState $to, MyEvent $event, MyContext $context)
```

The method named as **transitFrom\[SourceStateName\]To\[TargetStateName\]On\[EventName\]**, 
and parameterized as \[MyState, MyState, MyEvent, MyContext\] 
will be added into transition "A-(GoToB)->B" action list. 
When transiting from state 'A' to state 'B' on event 'GoToB', this method will be invoked.

```php
function transitFromAnyToBOnGoToB(MyState $from, MyState $to, MyEvent $event, MyContext $context)
```

**transitFromAnyTo[TargetStateName]On[EventName]** The method will be invoked when transit from any 
state to state 'B' on event 'GoToB'.

```php
function exitA(MyState $from, MyState $to, MyEvent $event, MyContext $context)
```

**exit[StateName]** The method will be invoked when exit state 'A'. So as the **entry[StateName]** , 
**beforeExitAny**/**afterExitAny** and **beforeEntryAny**/**afterEntryAny**.

***Other Supported Naming Patterns:***

```
transitFrom[fromStateName]To[toStateName]On[eventName]When[conditionName]
transitFrom[fromStateName]To[toStateName]On[eventName]
transitFromAnyTo[toStateName]On[eventName]
transitFrom[fromStateName]ToAnyOn[eventName]
transitFrom[fromStateName]To[toStateName]
on[eventName]
```

Those method conventions listed above also provided **AOP-like** functionalities, which 
provided build-in flexible extension capability for squirrel state machine at any 
granularity.

For more information, please refer to test case "*Pluf\Tests\ExtensionMethodCallTest*".
There is another way to define these AOP-like extension methods which is 
through fluent API, e.g.

```php
// the same effect as add method transitFromAnyToCOnToC in your state machine
builder
	->transit()
	->fromAny()
	->to("C")
	->on("ToC")
	->callMethod("fromAnyToC");
// the same effect as add method transitFromBToAnyOnToC in your state machine
builder
	->transit()
	->from("B")
	->toAny()
	->on("ToC")
	->callMethod("fromBToAny");
// the same effect as add method transitFromBToAny in your state machine
builder
	->transit()
	->from("B")
	->toAny()
	->onAny()
	->callMethod("fromBToAny");
```

Or through declarative PHP 8 Attributes, e.g.

```PHP
#[
     Transit(from="B", to="E", on="*",   callMethod="fromBToEOnAny"),
     Transit(from="*", to="E", on="ToE", callMethod="fromAnyToEOnToE")
]
class ExampleMachin{}
```

**Note**: These action methods will be attached to *matched and already existed transitions* 
but not to create any new transitions.
Multiple transitions can also be defined once at a time using following API, e.g.

```php
// transitions(A->B@A2B=>a2b, A->C@A2C=>a2c, A->D@A2D) will be defined at once
builder
	->transitions()
	->from(State::_A)
	->toAmong(State::B, State::C, State::D)
	->onEach(Event::A2B, Event::A2C, Event::A2D)
	->callMethod("a2b|a2c|_");

// transitions(A->_A@A2ANY=>DecisionMaker, _A->A@ANY2A) will be defined at once
builder
	->localTransitions()
	->between(State::A)
	->and(State::_A)
	->onMutual(Event::A2ANY, Event::ANY2A)
	->perform([new DecisionMaker("SomeLocalState"), null]);
```

More information can be found in *Pluf\Tests\Samples\DecisionStateSampleTest*;


## New State Machine Instance

After user defined state machine behaviour, user could create a new state machine instance through builder. 
Note, once the state machine instance is created from the builder, the builder cannot be used to define 
any new element of state machine anymore.

```java
function newStateMachine($initialStateId, ...$extraParams);
```

To create a new state machine instance from state machine builder, you need to pass following parameters.

1. ```initialStateId```: When started, the initial state of the state machine.
2. ```extraParams```: Extra parameters that needed for create new state machine instance. 

Set to *"new Object[0]"* for no extra parameters needed.

If user passed extra parameters while creating a new state machine instance, please be sure that 
StateMachineBuilderFactory also had defined type of extra parameters when creating the state 
machine builder. Otherwise, extra parameter will be ignored.

Extra parameters can be passed into state machine instance in two ways. One is through state machine 
constructor which means user need to define a constructor with the same parameters' type and order 
for the state machine instance. Another way is define a method named ```postConstruct``` and also 
with the same parameters' type and order.

If no extra parameters need to passed to state machine, user can simply call 
```newStateMachine($initialStateId)``` to create a new state machine instance.

New state machine from state machine builder. (In this case, no extra parameters need to be passed.)

```php
$stateMachine = builder->newStateMachine(MyState::Initial);
```

## Trigger Transitions

After state machine was created, user can fire events along with context to trigger transition 
inside state machine. e.g.

```php
$stateMachine->fire(MyEvent::Prepare, new MyContext("Testing"));
```
