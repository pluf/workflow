# Advanced Feature

## Define Hierarchical State

A hierarchical state may contain nested state. The child states may themselves have nested 
children and the nesting may proceed to any depth. When a hierarchical state is active, 
one and only one of its child states is active. The hierarchical state can be defined 
through API or annotation.

```php
function defineSequentialStatesOn(S parentStateId, S... childStateIds);
```

*builder.defineSequentialStatesOn(State.A, State.BinA, StateCinA)* defines two child states 
"BinA" and "CinA" under parent state "A", the first defined child state will also be the 
initial state of the hierarchical state "A". The same hierarchical state can also be 
defined through annotation, e.g.

```php
#[States({
    new State(name:"A", entryMethodCall:"entryA", exitMethodCall:"exitA"),
    new State(parent:"A", name:"BinA", entryMethodCall:"entryBinA", exitMethodCall:"exitBinA", initialState:true),
    new State(parent:"A", name:"CinA", entryMethodCall:"entryCinA", exitMethodCall:"exitCinA")
})]
```

## Define Parallel State

The parallel state encapsulates a set of child states which are simultaneously active when the parent 
element is active. The  parallel state can be defined through API or annotation both. e.g.

![ParallelStates](./images/ParallelStates.png)

```php
// defines two region states "RegionState1" and "RegionState2" under parent parallel state "Root"
builder.defineParallelStatesOn(MyState.Root, MyState.RegionState1, MyState.RegionState2);

builder.defineSequentialStatesOn(MyState.RegionState1, MyState.State11, MyState.State12);
builder
	.externalTransition()
		.from(MyState.State11)
		.to(MyState.State12)
		.on(MyEvent.Event1);

builder.defineSequentialStatesOn(MyState::RegionState2, MyState::State21, MyState::State22);
builder
	.externalTransition()
		.from(MyState::State21)
		.to(MyState::State22)
		.on(MyEvent::Event2);
```

or

```php
#[States({
    @State(name="Root", entryCallMethod="enterRoot", exitCallMethod="exitRoot", compositeType=StateCompositeType.PARALLEL),
    @State(parent="Root", name="RegionState1", entryCallMethod="enterRegionState1", exitCallMethod="exitRegionState1"),
    @State(parent="Root", name="RegionState2", entryCallMethod="enterRegionState2", exitCallMethod="exitRegionState2")
})]
```

To get current sub states of the parallel state

```php
stateMachine.getSubStatesOn(MyState::Root); // return list of current sub states of parallel state
    ```

When all the parallel states reached final state, a **Finish** context event will be fired.

## Define Context Event

    Context event means that user defined event has predefined context in the state machine. squirrel-foundation defined three type of context event for different use case.
    **Start/Terminate Event**: Event declared as start/terminate event will be used when state machine started/terminated. So user can differentiate the invoked action trigger, e.g. when state machine is starting and entering its initial state, user can differentiate these state entry action was invoked by start event.
    **Finish Event**: When all the parallel states reached final state, finish event will be automatically fired. User can define following transition based on finish event.
To define the context event, user has two way, annotation or builder API.

    ```java
    @ContextEvent(finishEvent="Finish")
    static class ParallelStateMachine extends AbstractStateMachine<...> {
    }
    ```

    or

    ```java
    StateMachineBuilder<...> builder = StateMachineBuilderFactory.create(...);
    ...
    builder.defineFinishEvent(HEvent.Start);
    builder.defineTerminateEvent(HEvent.Terminate);
    builder.defineStartEvent(HEvent.Finish);
    ```

*   **Using History States to Save and Restore the Current State**

    The history pseudo-state allows a state machine to remember its state configuration. A transition taking the history state as its target will return the state machine to this recorded configuration. If the 'type' of a history is "shallow", the state machine processor must record the direct  active children of its parent before taking any transition that exits the parent. If the 'type' of a history is "deep", the state machine processor must record all the active  descendants of the parent before taking any transition that exits the parent.
    Both API and annotation are supported to define history type of state. e.g.

    ```java
    // defined history type of state "A" as "deep"
    builder.defineSequentialStatesOn(MyState.A, HistoryType.DEEP, MyState.A1, MyState.A2)
    ```

    or

    ```java
    @State(parent="A", name="A1", entryCallMethod="enterA1", exitCallMethod="exitA1", historyType=HistoryType.DEEP)
    ```

    **Note:** Before 0.3.7, user need to define "HistoryType.DEEP" for each level of historical state, which is not quite convenient.(Thanks to [Voskuijlen](https://github.com/Voskuijlen) to provide solution [Issue33](https://github.com/hekailiang/squirrel/issues/33)). Now user only define "HistoryType.DEEP" at the top level of historical state, and all its children state historical information will be remembered.

*   **Transition Types**

    According to the UML specification, a transition may be one of these three kinds:

    > * *Internal Transition*
Implies that the Transition, if triggered, occurs without exiting or entering the source State (i.e., it does not cause a state change). This means that the entry or exit condition of the source State will not be invoked. An internal Transition can be taken even if the StateMachine is in one or more Regions nested within the associated State.
    > * *Local Transition*
    Implies that the Transition, if triggered, will not exit the composite (source) State, but it will exit and re-enter any state within the composite State that is in the current state configuration.
    > * *External Transition*
    Implies that the Transition, if triggered, will exit the composite (source) State

    squirrel-foundation supports both API and annotation to declare all kinds of transitions, e.g.

    ```java
    builder.externalTransition().from(MyState.A).to(MyState.B).on(MyEvent.A2B);
    builder.internalTransition().within(MyState.A).on(MyEvent.innerA);
    builder.localTransition().from(MyState.A).to(MyState.CinA).on(MyEvent.intoC)
    ```

    or

    ```java
    @Transitions({
        @Transition(from="A", to="B", on="A2B"), //default value of transition type is EXTERNAL
        @Transition(from="A", on="innerA", type=TransitionType.INTERNAL),
        @Transition(from="A", to="CinA", on="intoC", type=TransitionType.LOCAL),
    })
    ```

*   **Polymorphism Event Dispatch**

    During the lifecycle of the state machine, various events will be fired, e.g.

```
State Machine Lifecycle Events
|--StateMachineEvent                        /* Base event of all state machine event */
       |--StartEvent                            /* Fired when state machine started      */
       |--TerminateEvent                        /* Fired when state machine terminated   */
       |--TransitionEvent                       /* Base event of all transition event    */
            |--TransitionBeginEvent             /* Fired when transition began           */
            |--TransitionCompleteEvent          /* Fired when transition completed       */
            |--TransitionExceptionEvent         /* Fired when transition threw exception */
            |--TransitionDeclinedEvent          /* Fired when transition declined        */
            |--TransitionEndEvent               /* Fired when transition end no matter declined or complete */
```

    User can add a listener to listen StateMachineEvent, which means all events fired during state machine lifecycle will be caught by this listener, e.g.,

    ```java
    stateMachine.addStateMachineListener(new StateMachineListener<...>() {
            @Override
            public void stateMachineEvent(StateMachineEvent<...> event) {
                // ...
            }
    });
    ```

    **And** User can also add a listener to listen TransitionEvent through StateMachine.addTransitionListener, which means all events fired during each state transition including TransitionBeginEvent, TransitionCompleteEvent and TransitionEndEvent will be caught by this listener.
    **Or** user can add specific listener e.g. TransitionDeclinedListener to listen TransitionDeclinedEvent when transition request was declined.

*   **Declarative Event Listener**

    Adding above event listener to state machine sometime annoying to user, and too many generic types also makes code ugly to read. To simplify state machine usage, more important to provide a non-invasive integration, squirrel-foundation provides a declarative way to add event listener through following annotation, e.g.

    ```java
    static class ExternalModule {
        @OnTransitionEnd
        @ListenerOrder(10) // Since 0.3.1 ListenerOrder can be used to insure listener invoked orderly
        public void transitionEnd() {
            // method annotated with TransitionEnd will be invoked when transition end...
            // the method must be public and return nothing
        }
        
        @OnTransitionBegin
        public void transitionBegin(TestEvent event) {
            // method annotated with TransitionBegin will be invoked when transition begin...
        }
        
        // 'event'(E), 'from'(S), 'to'(S), 'context'(C) and 'stateMachine'(T) can be used in MVEL scripts
        @OnTransitionBegin(when="event.name().equals(\"toB\")")
        public void transitionBeginConditional() {
            // method will be invoked when transition begin while transition caused by event "toB"
        }
        
        @OnTransitionComplete
        public void transitionComplete(String from, String to, TestEvent event, Integer context) {
            // method annotated with TransitionComplete will be invoked when transition complete...
        }
        
        @OnTransitionDecline
        public void transitionDeclined(String from, TestEvent event, Integer context) {
            // method annotated with TransitionDecline will be invoked when transition declined...
        }
        
        @OnBeforeActionExecuted
        public void onBeforeActionExecuted(Object sourceState, Object targetState,
                Object event, Object context, int[] mOfN, Action<?, ?, ?,?> action) {
            // method annotated with OnAfterActionExecuted will be invoked before action invoked
        }
        
        @OnAfterActionExecuted
        public void onAfterActionExecuted(Object sourceState, Object targetState,
                Object event, Object context, int[] mOfN, Action<?, ?, ?,?> action) {
            // method annotated with OnAfterActionExecuted will be invoked after action invoked
        }
    
        @OnActionExecException
        public void onActionExecException(Action<?, ?, ?,?> action, TransitionException e) {
            // method annotated with OnActionExecException will be invoked when action thrown exception
        }
    }
    
    ExternalModule externalModule = new ExternalModule();
    fsm.addDeclarativeListener(externalModule);
    ...
    fsm.removeDeclarativeListener(externalModule);
    ```

    By doing this external module code does not need to implement any state machine listener interface. Only add few annotations on methods which will be hooked during transition phase. The parameters of method is also type safe, and will automatically be inferred to match corresponding event. This is a good approach for **Separation of Concerns**. User can find sample usage in *org.squirrelframework.foundation.fsm.StateMachineLogger*.

*   **Transition Extension Methods**

    Each transition event also has corresponding extension method on AbstractStateMachine class which is allowed to be extended in customer state machine implementation class.

    ```java
    protected void afterTransitionCausedException(Exception e, S fromState, S toState, E event, C context) {
    }
    
    protected void beforeTransitionBegin(S fromState, E event, C context) {
    }
    
    protected void afterTransitionCompleted(S fromState, S toState, E event, C context) {
    }
    
    protected void afterTransitionEnd(S fromState, S toState, E event, C context) {
    }
    
    protected void afterTransitionDeclined(S fromState, E event, C context) {
    }
    
    protected void beforeActionInvoked(S fromState, S toState, E event, C context) {
    }
    ```

    Typically, user can hook in your business processing logic in these extension methods during each state transition, while the various event listener serves as boundary of state machine based control system, which can interact with external modules (e.g. UI, Auditing, ESB and so on).
    For example, user can extend the method afterTransitionCausedException for environment clean up when exception happened during transition, and also notify user interface module to display error message  through TransitionExceptionEvent.

*   **Weighted Action**

    User can define action weight to adjust action execution order. The actions during state entry/exit and state transition are ordered in ascending order according to their weight value. Action weight is 0 by default. User has two way to set action weight.

    One is append weight number to method name and separated by ':'.

    ```java
    // define state entry action 'goEntryD' weight -150
    @State(name="D", entryCallMethod="goEntryD:-150")
    // define transition action 'goAToC1' weight +150
    @Transit(from="A", to="C", on="ToC", callMethod="goAToC1:+150")
    ```

    Another way is override weight method of Action class, e.g.

    ```java
    Action<...> newAction = new Action<...>() {
        ...
        @Override
        public int weight() {
            return 100;
        }
    }
    ```

    squirrel-foundation also support a conventional manner to declare action weight. The weight of method call action whose name started with '*before*' will be set to 100, so as the name started with '*after*' will be set to -100. Generally it means that the action method name started with 'before' will be invoked at first, while the action method name started with 'after' will be invoked at last. "method1:ignore" means method1 will not be invoked.

    For more information, please refer to test case '*org.squirrelframework.foundation.fsm.WeightedActionTest*';

*   **Asynchronized Execution**

    **@AsyncExecute** annotation can be used on method call action and declarative event listener to indicate that this action or event listener will be executed asynchronously, e.g.
    Define asynchronously invoked action method:

    ```java
    @ContextInsensitive
    @StateMachineParameters(stateType=String.class, eventType=String.class, contextType=Void.class)
    public class ConcurrentSimpleStateMachine extends AbstractUntypedStateMachine {
        // No need to specify constructor anymore since 0.2.9
        // protected ConcurrentSimpleStateMachine(ImmutableUntypedState initialState,
        //    Map<Object, ImmutableUntypedState> states) {
        //  super(initialState, states);
        // }
    
        @AsyncExecute
        protected void fromAToB(String from, String to, String event) {
            // this action method will be invoked asynchronously
        }
    }
    ```

    Define asynchronously dispatched event:

    ```java
    public class DeclarativeListener {
        @OnTransitionBegin
        @AsyncExecute
        public void onTransitionBegin(...) {
            // transition begin event will be dispatched asynchronously to this listener method
        }
    }
    ```

    Asynchronous execution task will be submit to a *ExecutorService*. User can register your ExecutorService implementation instance through *SquirrelSingletonProvider*, e.g.

    ```java
    ExecutorService executorService = Executors.newFixedThreadPool(1);
    SquirrelSingletonProvider.getInstance().register(ExecutorService.class, executorService);
    ```

    If no ExecutorService instance was registered, *SquirrelConfiguration* will provide a default one.

*   **State Machine PostProcessor**

    User can register post processor for specific type of state machine in order to adding post process logic after state machine instantiated, e.g.

    ```java
    // 1 User defined a state machine interface
    interface MyStateMachine extends StateMachine<MyStateMachine, MyState, MyEvent, MyContext> {
    . . .
    }
    
    // 2 Both MyStateMachineImpl and MyStateMachineImplEx are implemented MyStateMachine
    class MyStateMachineImpl implements MyStateMachine {
        . . .
    }
    class MyStateMachineImplEx implements MyStateMachine {
        . . .
    }
    
    // 3 User define a state machine post processor
    MyStateMachinePostProcessor implements SquirrelPostProcessor<MyStateMachine> {
        void postProcess(MyStateMachine component) {
            . . .
        }
    }
    
    // 4 User register state machine post process
    SquirrelPostProcessorProvider.getInstance().register(MyStateMachine.class, MyStateMachinePostProcessor.class);
    ```

    For this case, when user created both MyStateMachineImpl and MyStateMachineImplEx instance, the registered post processor MyStateMachinePostProcessor will be called to do some work.

*   **State Machine Export**

    **SCXMLVisitor** can be used to export state machine definition in [SCXML] [2] document.

    ```java
    SCXMLVisitor visitor = SquirrelProvider.getInstance().newInstance(SCXMLVisitor.class);
    stateMachine.accept(visitor);
    visitor.convertSCXMLFile("MyStateMachine", true);
    ```

    BTW, user can also call *StateMachine.exportXMLDefinition(true)* to export beautified XML definition.
    **DotVisitor** can be used to generate state diagram which can be viewed by [GraphViz] [3].

    ```java
    DotVisitor visitor = SquirrelProvider.getInstance().newInstance(DotVisitor.class);
    stateMachine.accept(visitor);
    visitor.convertDotFile("SnakeStateMachine");
    ```

*   **State Machine Import**

    **UntypedStateMachineImporter** can be used to import state machine SCXML-similar definition which was  exported by SCXMLVisitor or handwriting definition. UntypedStateMachineImporter will build a UntypedStateMachineBuilder according to the definition which can later be used to create state machine instances.

    ```java
    UntypedStateMachineBuilder builder = new UntypedStateMachineImporter().importDefinition(scxmlDef);
    ATMStateMachine stateMachine = builder.newAnyStateMachine(ATMState.Idle);
    ```

    *Note: The UntypedStateMachineImporter provided an XML-style to define the state machine just like the state machine builder API or declarative annotations. The SCXML-similar definition is not equal to standard SCXML.*

*   **Save/Load State Machine Data**

    User can save data of state machine when state machine is in idle status.

    ```java
    StateMachineData.Reader<MyStateMachine, MyState, MyEvent, MyContext>
        savedData = stateMachine.dumpSavedData();
    ```

    And also user can load above *savedData* into another state machine whose status is terminated or just initialized.

    ```java
    newStateMachineInstance.loadSavedData(savedData);
    ```

    **NOTE**: The state machine data can be serialized to/deserialized from Base64 encoded string with the help of *ObjectSerializableSupport* class.

*   **State Machine Configuration**

    When creating new state machine instance, user can configure its behavior through   *StateMachineConfiguration*, e.g.

    ```java
    UntypedStateMachine fsm = builder.newUntypedStateMachine("a",
         StateMachineConfiguration.create().enableAutoStart(false)
                .setIdProvider(IdProvider.UUIDProvider.getInstance()),
         new Object[0]); // since 0.3.0
    fsm.fire(TestEvent.toA);
    ```

    The sample code above is used to create a state machine instance with UUID as its identifier and disable auto start function.
    StateMachineConfigure can also be set on state machine builder which means all the state machine instance created by ```builder.newStateMachine(S initialStateId)``` or ```builder.newStateMachine(S initialStateId, Object... extraParams)``` will use this configuration.

*   **State Machine Diagnose**

    *StateMachineLogger* is used to observe internal status of the state machine, like the execution performance, action calling sequence, transition progress and so on, e.g.

    ```java
    StateMachine<?,?,?,?> stateMachine = builder.newStateMachine(HState.A);
    StateMachineLogger fsmLogger = new StateMachineLogger(stateMachine);
    fsmLogger.startLogging();
    ...
    stateMachine.fire(HEvent.B2A, 1);
    ...
    fsmLogger.terminateLogging();
    -------------------------------------------------------------------------------------------
    Console Log:
    HierachicalStateMachine: Transition from "B2a" on "B2A" with context "1" begin.
    Before execute method call action "leftB2a" (1 of 6).
    Before execute method call action "exitB2" (2 of 6).
    ...
    Before execute method call action "entryA1" (6 of 6).
    HierachicalStateMachine: Transition from "B2a" to "A1" on "B2A" complete which took 2ms.
    ...
    ```

    *Since v0.3.0 state machine logger can be used more easy way by just set StateMachineConfiguration enable debug mode to ture, e.g.*

    ```
    StateMachine<?,?,?,?> stateMachine = builder.newStateMachine(HState.A,
            StateMachineConfiguration.create().enableDebugMode(true),
            new Object[0]);
    ```
    
    *StateMachinePerformanceMonitor* can be used to monitor state machine execution performance information, including total transition times count, average transition consumed time and so on, e.g.

    ```java
    final UntypedStateMachine fsm = builder.newStateMachine("D");
    final StateMachinePerformanceMonitor performanceMonitor =
                new StateMachinePerformanceMonitor("Sample State Machine Performance Info");
    fsm.addDeclarativeListener(performanceMonitor);
    for (int i = 0; i < 10000; i++) {
        fsm.fire(FSMEvent.ToA, 10);
        fsm.fire(FSMEvent.ToB, 10);
        fsm.fire(FSMEvent.ToC, 10);
        fsm.fire(FSMEvent.ToD, 10);
    }
    fsm.removeDeclarativeListener(performanceMonitor);
    System.out.println(performanceMonitor.getPerfModel());
    -------------------------------------------------------------------------------------------
    Console Log:
    ========================== Sample State Machine Performance Info ==========================
    Total Transition Invoked: 40000
    Total Transition Failed: 0
    Total Transition Declained: 0
    Average Transition Comsumed: 0.0004ms
        Transition Key      Invoked Times   Average Time        Max Time    Min Time
        C--{ToD, 10}->D     10000           0.0007ms            5ms         0ms
        B--{ToC, 10}->C     10000           0.0001ms            1ms         0ms
        D--{ToA, 10}->A     10000           0.0009ms            7ms         0ms
        A--{ToB, 10}->B     10000           0.0000ms            1ms         0ms
    Total Action Invoked: 40000
    Total Action Failed: 0
    Average Action Execution Comsumed: 0.0000ms
        Action Key          Invoked Times   Average Time        Max Time    Min Time
        instan...Test$1     40000           0.0000ms            1ms         0ms
    ========================== Sample State Machine Performance Info ==========================
    ```

    Add **@LogExecTime** on action method will log out the execution time of the method. And also add the @LogExecTime on state machine class will log out all the action method execution time. For example, the execution time of method *transitFromAToBOnGoToB* will be logged out.

    ```java
    @LogExecTime
    protected void transitFromAToBOnGoToB(MyState from, MyState to, MyEvent event, MyContext context)
    ```

*   **Timed State**

    A **timed state** is a state that can delay or periodically trigger specified event after state entered. Timed task will be submit to a *ScheduledExecutorService*. User can register your ScheduledExecutorService implementation instance through *SquirrelSingletonProvider*, e.g.

    ```java
    ScheduledExecutorService scheduler = Executors.newScheduledThreadPool(1);
    SquirrelSingletonProvider.getInstance().register(ScheduledExecutorService.class, scheduler);
    ```

    If no ScheduledExecutorService instance was registered, *SquirrelConfiguration* will provide a default one. After that, a timed state can be defined by state machine builder, e.g.

    ```java
    // after 50ms delay fire event "FIRST" every 100ms with null context
    builder.defineTimedState("A", 50, 100, "FIRST", null);
    builder.internalTransition().within("A").on("FIRST");
    ```

    **NOTE**: Make sure timed state must be defined before describe its transitions or entry/exit actions. *timeInterval* less than or equal to 0 will be considered only execute once after *initialDelay*.

*   **Linked State (so called Submachine State)**

    A **linked state** specifies the insertion of the specification of a submachine state machine. The state machine that contains the linked state is called the containing state machine. The same state machine may be a submachine more than once in the context of a single containing state machine.

    A linked state is semantically equivalent to a composite state. The regions of the submachine state machine are the regions of the composite state. The entry, exit, and behavior actions and internal transitions are defined as part of the state. Submachine state is a decomposition mechanism that allows factoring of common behaviors and their reuse.
    The linked state can be defined by following sample code.

    ```java
    builderOfTestStateMachine.definedLinkedState(LState.A, builderOfLinkedStateMachine, LState.A1);
    ```

*   ~~**JMX Support**~~

    Since 0.3.3, user can remote monitor state machine instance(e.g. current status, name) and modify configurations(e.g. toggle loggings/toggle performance monitor/remote fire event) at runtime. All the state machine instances information will be under "org.squirrelframework" domain. The following sample code shows how to enable JMX support.

    ```java
    UntypedStateMachineBuilder builder = StateMachineBuilderFactory.create(...);
    builder.setStateMachineConfiguration(StateMachineConfiguration.create().enableRemoteMonitor(true));
    ```
    
    **NOTE**: JMX feature support deprecated since 0.3.9-SNAPSHOT.
