<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Container\ContainerInterface;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;

class CompiledListenerProviderBase implements ListenerProviderInterface
{
    protected $container;

    protected $listeners = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getListenersForEvent(EventInterface $event): iterable
    {
        foreach ($this->listeners as $listener) {
            if ($event instanceof $listener['type']) {
                switch ($listener['entryType']) {
                    case ListenerFunctionEntry::class:
                        yield $listener['listener'];
                        break;
                    case ListenerStaticMethodEntry::class:
                        yield [$listener['class'], $listener['method']];
                        break;
                    case ListenerServiceEntry::class:
                        yield function (EventInterface $event) use ($listener) {
                            $this->container->get($listener['serviceName'])->{$listener['method']}($event);
                        };
                        break;
                }
            }
        }
    }
}
