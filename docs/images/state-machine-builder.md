# State Machine Builder

Pluf Workflow supports both fluent API and declarative manner to declare a state machine, and 
also enable user to define the action methods in a straightforward manner.

StateMachine consists of four type parameters.

* state machine
* state
* event
* context

State machine builder is used to generate state machine definition. 

StateMachineBuilder can be created by StateMachineBuilderFactory.

The StateMachineBuilder is composed of 
*TransitionBuilder (InternalTransitionBuilder / LocalTransitionBuilder / ExternalTransitionBuilder) 
which is used to build transition between states, and EntryExitActionBuilder which is used to build 
the actions during entry or exit state.

The internal state is implicitly built during transition creation or state action creation.

All the state machine instances created by the same state machine builder share the same 
definition data for memory usage optimize.

State machine builder generate state machine definition in a lazy manner. When builder create 
first state machine instance, the state machine definition will be generated which is time consumed. 
But after state machine definition generated, the following state machine instance creation will be much 
faster. Generally, state machine builder should be reused as much as possible.

In order to create a state machine, user need to create state machine builder first. For example:

```php
$builder = StateMachineBuilderFactory::create(MyStateMachine::class, MyState::class, MyEvent::class, MyContext::class);
```

The state machine builder takes for parameters which are type of state machine, state,
event and context.

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

