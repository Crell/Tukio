<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\ListenerFunctionEntry;
use Crell\Tukio\Entry\ListenerServiceEntry;
use Crell\Tukio\Entry\ListenerStaticMethodEntry;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class CompiledListenerProviderBase implements ListenerProviderInterface
{
    protected ContainerInterface $container;

    // This nested array will be generated by the compiler in a subclass.  It's listed here for reference only.
    // Its structure is an ordered list of array definitions, each of which corresponds to one of the defined
    // entry types in the classes seen in getListenerForEvent().  See each class's getProperties() method for the
    // exact structure.
    /** @var array<mixed> */
    protected const LISTENERS = [];

    protected const OPTIMIZED = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        if (isset(static::OPTIMIZED[$event::class])) {
            $count = count(static::OPTIMIZED[$event::class]);
            $ret = [];
            for ($i = 0; $i < $count; ++$i) {
                $listener = static::OPTIMIZED[$event::class];
                if ($event instanceof $listener['type']) {
                    // Turn this into a match() in PHP 8.
                    switch ($listener['entryType']) {
                        case ListenerFunctionEntry::class:
                            $ret[] = $listener['listener'];
                            break;
                        case ListenerStaticMethodEntry::class:
                            $ret[] = [$listener['class'], $listener['method']];
                            break;
                        case ListenerServiceEntry::class:
                            $ret[] = function (object $event) use ($listener): void {
                                $this->container->get($listener['serviceName'])->{$listener['method']}($event);
                            };
                            break;
                        default:
                            throw new \RuntimeException(sprintf('No such listener type found in compiled container definition: %s', $listener['entryType']));
                    }
                }
            }
            return $ret;
        }

        $count = count(static::LISTENERS);
        $ret = [];
        for ($i = 0; $i < $count; ++$i) {
            /** @var array<mixed> $listener */
            $listener = static::LISTENERS[$i];
            if ($event instanceof $listener['type']) {
                // Turn this into a match() in PHP 8.
                switch ($listener['entryType']) {
                    case ListenerFunctionEntry::class:
                        $ret[] = $listener['listener'];
                        break;
                    case ListenerStaticMethodEntry::class:
                        $ret[] = [$listener['class'], $listener['method']];
                        break;
                    case ListenerServiceEntry::class:
                        $ret[] = function (object $event) use ($listener): void {
                            $this->container->get($listener['serviceName'])->{$listener['method']}($event);
                        };
                        break;
                    default:
                        throw new \RuntimeException(sprintf('No such listener type found in compiled container definition: %s', $listener['entryType']));
                }
            }
        }
        return $ret;
    }
}
