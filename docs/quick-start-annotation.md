# Quick Start

To quickly try Pluf Workflow state machine functions, please create a PHP 8 composer project and 
include pluf/workflow dependency properly. 

Then just run following sample code.


First of all define State Machine Event

```php
class FSMEvent {
    public const ToA = "ToA";
    public const ToB = "ToB";
    public const ToC = ToC";
    public const ToD = "ToD";
}
```

Define State Machine Class and its transitions

```php
#[
	StateMachineParameters(
		stateType: 'string', 
		eventType: 'string', 
		contextType: Integer::class),
	State(
		name: 'A'),
	State(
		name: 'B',
		entryCallMethod: 'ontoB'),
	Transition(
		from: 'A',
		to: 'B',
		on: 'ToB',
		callMethod: 'fromAToB'),
]
class StateMachineSample { 
	public function fromAToB(){}
	public function ontoB(){}
}
```

Use State Machine

```php
$builder = new StateMachineBilder();
$fsm = $builder
	->fromClass(StateMachineSample::class)
	->build();
$fsm->fire(FSMEvent::ToB, [
		'a' => 10
	]);
echo "Current state is " . $fsm->getCurrentState());
```

At now you may have many questions about the sample code, please be patient. The following user 
guide will answer most of your questions. But before getting into the details, it requires you 
have basic understanding on state machine concepts. These materials are good for understanding 
state machine concepts. 

[[state-machine-diagrams]][9] 
[[qt-state-machine]][10]

[9]: http://www.uml-diagrams.org/state-machine-diagrams.html
[10]: http://qt-project.org/doc/qt-4.8/statemachine-api.html


