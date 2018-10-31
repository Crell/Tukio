<?php
declare(strict_types=1);

namespace Crell\Tukio\Workflow;

use Fig\EventDispatcher\ParameterDeriverTrait;
use Psr\EventDispatcher\EventInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class WorkflowProvider implements ListenerProviderInterface
{
    use ParameterDeriverTrait;

    /** @var array */
    protected $listeners = [];

    public function getListenersForEvent(EventInterface $event) : iterable
    {
        if (!$event instanceof WorkflowTaskInterface) {
            return [];
        }

        /** WorkflowTaskInterface $event */
        $name = $event->workflowName();

        foreach ($this->listeners[$name] as $type => $listeners) {
            foreach ($listeners as $listener) {
                if ($event instanceof $type) {
                    yield $listener;
                }
            }
        }
    }

    public function addListener(callable $listener, string $workflowName, string $type = null) : void
    {
        $type = $type ?? $this->getParameterType($listener);

        $this->listeners[$workflowName][$type][] = $listener;
    }

}
