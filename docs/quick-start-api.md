# Get Starting

Pluf Workflow supports both fluent API and declarative manner to declare a state machine, and 
also enable user to define the action methods in a straightforward manner.

StateMachine consists of four type parameters.

* state machine
* state
* event
* context

State machine builder is used to generate state machine definition. 

The internal state is implicitly built during transition creation or state action creation.

All the state machine instances created by the same state machine builder share the same 
definition data for memory usage optimize.

In order to create a state machine, user need to create state machine builder first. For example:

```php
$builder = StateMachineBuilderFactory::create(MyStateMachine::class, MyState::class, MyEvent::class, MyContext::class);
```

The state machine builder takes for parameters which are type of state machine, state,
event and context.

After state machine builder was created, we can use fluent API to define state/transition/action 
of the state machine.

```php
$builder
	->externalTransition()
		->from(MyState::A)
		->to(MyState::B)
		->on(MyEvent::GoToB);
```


After user defined state machine behaviour, user could create a new state machine instance through builder. 
Note, once the state machine instance is created from the builder, the builder cannot be used to define 
any new element of state machine anymore.

```php
$fsm = $builder->newStateMachine($initialStateId);
```

To create a new state machine instance from state machine builder, you need to pass following parameters.

1. ```initialStateId```: When started, the initial state of the state machine.
2. ```extraParams```: Extra parameters that needed for create new state machine instance. 

If user passed extra parameters while creating a new state machine instance, please be sure that 
StateMachineBuilderFactory also had defined type of extra parameters when creating the state 
machine builder. Otherwise, extra parameter will be ignored.

After state machine was created, user can fire events along with context to trigger transition 
inside state machine. e.g.

```php
$stateMachine->fire(MyEvent::Prepare, new MyContext("Testing"));
```

