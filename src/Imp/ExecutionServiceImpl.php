<?php
namespace Pluf\Workflow\Imp;

use Pluf\Di\Container;
use Pluf\Di\Invoker;
use Pluf\Di\ParameterResolver\DefaultValueResolver;
use Pluf\Di\ParameterResolver\ResolverChain;
use Pluf\Di\ParameterResolver\Container\ParameterNameContainerResolver;
use Pluf\Workflow\Action;
use Pluf\Workflow\ActionExecutionService;
use Pluf\Workflow\ErrorCodes;
use Pluf\Workflow\Exceptions\TransitionException;
use Pluf\Workflow\Imp\Events\AfterExecActionEventImpl;
use Pluf\Workflow\Imp\Events\BeforeExecActionEventImpl;
use Pluf\Workflow\Imp\Events\ExecActionExceptionEventImpl;
use Throwable;

/**
 * Following eventes are supported
 *
 * - error
 * - before
 * - after
 *
 * @author maso
 *        
 */
class ExecutionServiceImpl implements ActionExecutionService
{

    use AssertTrait;
    use EventHandlerTrait;

    protected array $actionBuckets = [];

    protected bool $dummyExecution = false;

    private int $actionTotalSize = 0;

    public ?Container $container = null;

    /**
     * Creates new instance of the service
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ActionExecutionService::begin()
     */
    public function begin(string $bucketName): void
    {
        array_push($this->actionBuckets, [
            $bucketName,
            []
        ]);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ActionExecutionService::defer()
     */
    public function defer($action, $from, $to, $event, $context, $stateMachine): void
    {
        $this->assertNotEmpty($action, "Action cannot be null.");
        $this->assertTrue(sizeof($this->actionBuckets) > 0, "Action bucket currently is empty. Make sure execution service is began.");
        array_push($this->actionBuckets[sizeof($this->actionBuckets) - 1][1], ActionContext::get($action, $from, $to, $event, $context, $stateMachine, ++ $this->actionTotalSize));
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ActionExecutionService::execute()
     */
    public function execute(): void
    {
        try {
            while (sizeof($this->actionBuckets) > 0) {
                $this->executeActions();
            }
        } finally {
            $this->reset();
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ActionExecutionService::reset()
     */
    public function reset(): void
    {
        $this->actionBuckets = [];
        $this->actionTotalSize = 0;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Pluf\Workflow\ActionExecutionService::setDummyExecution()
     */
    public function setDummyExecution(bool $dummyExecution): void
    {
        $this->dummyExecution = $dummyExecution;
    }

    private function doExecute(string $bucketName, array $bucketActions): void
    {
        // TODO: make a contaner and invoker
        // TODO: reset most be supported
        // $this->assertNotEmpty($bucketActions, "Action bucket cannot be empty when executing.");
        $actionSize = sizeof($bucketActions);
        $container = $this->container;
        for ($i = 0; $i < $actionSize; ++ $i) {
            $actionContext = $bucketActions[$i];
            if ($actionContext->action->weight != Action::IGNORE_WEIGHT) {
                try {
                    $this->fire('before', BeforeExecActionEventImpl::get($actionContext->position, $this->actionTotalSize, $actionContext));
                    if ($this->dummyExecution) {
                        continue;
                    }
                    $container = $this->run($actionContext, $container);
                } catch (Throwable $e) {
                    $te = new TransitionException('Fail to execute the action: '.$e->getMessage(), ErrorCodes::FSM_TRANSITION_ERROR, $e, $actionContext->from, $actionContext->to, $actionContext->event, $actionContext->context, $actionContext->action->name);
                    $this->fire('error', ExecActionExceptionEventImpl::get($te, $i + 1, $actionSize, $actionContext));
                    throw $te;
                } finally {
                    $this->fire('after', AfterExecActionEventImpl::get($i + 1, $actionSize, $actionContext));
                }
            }
        }
    }

    private function executeActions(): void
    {
        $actionBucket = array_pop($this->actionBuckets);
        $bucketName = $actionBucket[0];
        $actionContexts = $actionBucket[1];
        $this->doExecute($bucketName, $actionContexts);
    }

    private function run(ActionContext $context, Container $containerOrigin): Container
    {
        // init container to isolate for each action
        $container = new Container($containerOrigin);
        $container['from'] = Container::value($context->from);
        $container['to'] = Container::value($context->to);
        $container['event'] = Container::value($context->event);
        $container['context'] = Container::value($context->context);
        $container['stateMachine'] = Container::value($context->stateMachine);
        $container['position'] = Container::value($context->position);
        $container['stateMachineImplementation'] = Container::value($context->stateMachine->getImplementation());
        
        if(is_array($context->context)){
            foreach ($context->context as $key=>$value){
                if(!UtilImpl::isReservedKey($key)) {
                    $container[$key] = Container::value($value);
                }
            }
        }

        // invoke the action
        $invoker = new Invoker(new ResolverChain([
            new ParameterNameContainerResolver($container),
            new DefaultValueResolver()
        ]), $container);
        $container['invoker'] = Container::value($invoker);

        $invoker->call($context->action);

        return $container;
    }
}


    
