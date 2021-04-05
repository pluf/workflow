<?php
namespace Pluf\Workflow\Imp\Events;

class AbstractExecActionEvent
{
}



//     static abstract class AbstractExecActionEvent<T extends StateMachine<T, S, E, C>, S, E, C>
//             implements ActionEvent<T, S, E, C> {
//         private ActionContext<T, S, E, C> executionContext;
//         private int pos;
//         private int size;

//         AbstractExecActionEvent(int pos, int size, ActionContext<T, S, E, C> actionContext) {
//             this.pos = pos;
//             this.size = size;
//             this.executionContext = actionContext;
//         }

//         @Override
//         public Action<T, S, E, C> getExecutionTarget() {
//             // user can only read action info but cannot invoke action in the listener method
//             return new UncallableActionImpl<T, S, E, C>(executionContext.action);
//         }

//         @Override
//         public S getFrom() {
//             return executionContext.from;
//         }

//         @Override
//         public S getTo() {
//             return executionContext.to;
//         }

//         @Override
//         public E getEvent() {
//             return executionContext.event;
//         }

//         @Override
//         public C getContext() {
//             return executionContext.context;
//         }

//         @Override
//         public T getStateMachine() {
//             return executionContext.fsm;
//         }

//         @Override
//         public int[] getMOfN() {
//             return new int[]{pos, size};
//         }
//     }