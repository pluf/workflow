<?php
namespace Pluf\Workflow;

/**
 * State machine action executor.
 *
 * The action defined during state entry/exit and transition will be
 * collected by action executor, and executed later together. The executor can execute actions in
 * synchronize and synchronize manner.
 *
 * @author maso
 */
interface ActionExecutionService
{

    /**
     * Begin a action execution collection in the bucket.
     */
    function begin(string $bucketName): void;

    /**
     * Execute all the actions collected by front bucket.
     *
     * Each action executed with container and invoker. Soe there may be any
     * parameter for functions.
     */
    function execute(): void;

    /**
     * Reset all deferred actions and its count
     */
    function reset(): void;

    /**
     * Set dummy execution true will cause no action being actually invoked when calling {@link ActionExecutionService#execute()}.
     *
     * @param
     *            dummyExecution
     */
    function setDummyExecution(bool $dummyExecution): void;

    /**
     * Add action and all the execution parameters into execution context;
     *
     * @param
     *            action activity to be executed
     * @param
     *            from source state
     * @param
     *            to target state
     * @param
     *            event activity cause
     * @param
     *            context external environment context
     * @param
     *            stateMachine state machine reference
     */
    function defer(Action $action, $from, $to, $event, $context, $stateMachine): void;

    // /**
    // * Add before action execution listener which can be used for monitoring execution
    // * @param listener action execution listener
    // */
    // void addExecActionListener(BeforeExecActionListener<T, S, E, C> listener);

    // /**
    // * Remove before action execution listener
    // * @param listener action execution listener
    // */
    // void removeExecActionListener(BeforeExecActionListener<T, S, E, C> listener);

    // /**
    // * Add after action execution listener which can be used for monitoring execution
    // * @param listener action execution listener
    // */
    // void addExecActionListener(AfterExecActionListener<T, S, E, C> listener);

    // /**
    // * Remove after action execution listener
    // * @param listener action execution listener
    // */
    // void removeExecActionListener(AfterExecActionListener<T, S, E, C> listener);

    // /**
    // * Add action execution exception listener which can be used for monitoring execution
    // * @param listener action execution exception listener
    // */
    // void addExecActionExceptionListener(ExecActionExceptionListener<T, S, E, C> listener);

    // /**
    // * Remove action execution exception listener
    // * @param listener action execution exception listener
    // */
    // void removeExecActionExceptionListener(ExecActionExceptionListener<T, S, E, C> listener);

    // /**
    // * Action execution event
    // */
    // public interface ActionEvent<T extends StateMachine<T, S, E, C>, S, E, C> extends SquirrelEvent {
    // Action<T, S, E, C> getExecutionTarget();
    // S getFrom();
    // S getTo();
    // E getEvent();
    // C getContext();
    // T getStateMachine();
    // int[] getMOfN();
    // }

    // public interface BeforeExecActionEvent<T extends StateMachine<T, S, E, C>, S, E, C> extends ActionEvent<T, S, E, C> {}

    // /**
    // * Before Action execution listener
    // */
    // public interface BeforeExecActionListener<T extends StateMachine<T, S, E, C>, S, E, C> {
    // public static final String METHOD_NAME = "beforeExecute";
    // public static final Method METHOD = ReflectUtils.getMethod(
    // BeforeExecActionListener.class, METHOD_NAME, new Class[]{BeforeExecActionEvent.class});
    // void beforeExecute(BeforeExecActionEvent<T, S, E, C> event);
    // }

    // public interface AfterExecActionEvent<T extends StateMachine<T, S, E, C>, S, E, C> extends ActionEvent<T, S, E, C> {}

    // /**
    // * After Action execution listener
    // */
    // public interface AfterExecActionListener<T extends StateMachine<T, S, E, C>, S, E, C> {
    // public static final String METHOD_NAME = "afterExecute";
    // public static final Method METHOD = ReflectUtils.getMethod(
    // AfterExecActionListener.class, METHOD_NAME, new Class[]{AfterExecActionEvent.class});
    // void afterExecute(AfterExecActionEvent<T, S, E, C> event);
    // }

    // public interface ExecActionExceptionEvent<T extends StateMachine<T, S, E, C>, S, E, C> extends ActionEvent<T, S, E, C> {
    // TransitionException getException();
    // }

    // public interface ExecActionExceptionListener<T extends StateMachine<T, S, E, C>, S, E, C> {
    // public static final String METHOD_NAME = "executeException";
    // public static final Method METHOD = ReflectUtils.getMethod(
    // ExecActionExceptionListener.class, METHOD_NAME,
    // new Class[]{ExecActionExceptionEvent.class});
    // void executeException(ExecActionExceptionEvent<T, S, E, C> event);
    // }
}

