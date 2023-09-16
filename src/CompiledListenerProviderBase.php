<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class CompiledListenerProviderBase implements ListenerProviderInterface
{
    // This nested array will be generated by the compiler in a subclass.  It's listed here for reference only.
    // Its structure is an ordered list of array definitions, each of which corresponds to one of the defined
    // entry types in the classes seen in getListenerForEvent().  See each class's getProperties() method for the
    // exact structure.
    /** @var array<mixed> */
    protected array $listeners = [];

    /** @var array<class-string, mixed>  */
    protected array $optimized = [];

    public function __construct(protected ContainerInterface $container) {}

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        if (isset($this->optimized[$event::class])) {
            return $this->optimized[$event::class];
        }

        $count = count($this->listeners);
        $ret = [];
        for ($i = 0; $i < $count; ++$i) {
            /** @var array<mixed> $listener */
            $listener = $this->listeners[$i];
            if ($event instanceof $listener['type']) {
                $ret[] = $listener['callable'];
            }
        }
        return $ret;
    }
}
