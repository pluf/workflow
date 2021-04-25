<?php
namespace Pluf\Workflow\Actions;

use Pluf\Workflow\Imp\AssertTrait;
use Pluf\Di\Invoker;

class MethodCallActionImpl extends AbstractAction
{
    public string $method;

    // final static Logger logger = LoggerFactory.getLogger(MethodCallActionImpl.class);
    // private bool $logExecTime;
    // private string $methodDesc;
    // private string $executeWhenExpr;
    // private ExecutionContext $executionContext;
    // private bool $isAsync;
    // private int $timeout;

    /**
     * Creates a new instace of method caller
     *
     * @param string $method to call from state machine implementation
     * @param int $weight of the method
     */
    public function __construct(string $method, int $weight = 1)
    {
        parent::__construct('method#'.$method, $weight);
        $this->method = $method;
        // this.executionContext = executionContext;

        // AsyncExecute asyncAnnotation = method.getAnnotation(AsyncExecute.class);
        // this.isAsync = asyncAnnotation!=null;
        // this.timeout = asyncAnnotation!=null ? asyncAnnotation.timeout() : -1;

        // logExecTime = ReflectUtils.isAnnotatedWith(method, LogExecTime.class);
        // if(!logExecTime) {
        // logExecTime = method.getDeclaringClass().getAnnotation(LogExecTime.class) != null;
        // }

        // TODO: maso, 2021: support execute when attribute
        // ExecuteWhen executeWhen = method.getAnnotation(ExecuteWhen.class);
        // if(executeWhen!=null) {
        // executeWhenExpr = executeWhen.value();
        // executionContext.getScriptManager().compile(executeWhenExpr);
        // } else {
        // executeWhenExpr = null;
        // }

        // methodDesc = ReflectUtils.logMethod(method);
    }

    public function __invoke(/* $from, $to, $event, $context, StateMachine $stateMachine,  */Invoker $invoker)
    {
        // TODO: maso, 2021: support execute when attribute
        // if(executeWhenExpr!=null) {
        // Map<String, Object> variables = new HashMap<String, Object>();
        // // variables.put("from", from);
        // // variables.put("to", to);
        // // variables.put("event", event);
        // variables.put("context", context);
        // // variables.put("stateMachine", stateMachine);
        // boolean isAllowed = executionContext.getScriptManager().evalBoolean(executeWhenExpr, variables);
        // if(!isAllowed) return;
        // }

        // Object[] paramValues = Lists.newArrayList(from, to, event, context).
        // subList(0, executionContext.getMethodCallParamTypes().length).toArray();
        // if(logExecTime && logger.isDebugEnabled()) {
        // Stopwatch sw = Stopwatch.createStarted();
        // ReflectUtils.invoke(method, stateMachine, paramValues);
        // logger.debug("Execute Method \""+methodDesc+"\" tooks "+sw+".");
        // } else {
        // ReflectUtils.invoke(method, stateMachine, paramValues);
        // }
        $invoker->call('stateMachineImplementation::' . $this->method);
    }

    public function equals($obj): bool
    {
        if ($this == $obj) {
            return true;
        }
        if (empty($obj)) {
            return false;
        }
        // if($obj instanceof MethodCallActionProxyImpl && obj.equals(this))
        // return true;
        // if ($this::class != $obj::class || ! $method -> equals($obj->method)) {
        // return false;
        // }
        return true;
    }

    public function __toString(): string
    {
        return "method#" . $this->method . ":" . $this->weight;
    }
}

