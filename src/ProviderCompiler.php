<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\CompileableListenerEntry;
use Crell\Tukio\Entry\ListenerFunctionEntry;
use Crell\Tukio\Entry\ListenerServiceEntry;
use Crell\Tukio\Entry\ListenerStaticMethodEntry;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class ProviderCompiler
{
    /**
     * Compiles a provided ProviderBuilder to a named class on disk.
     *
     * @param ProviderBuilder $listeners
     *   The set of listeners to compile.
     * @param resource $stream
     *   A writeable stream to which to write the compiled class.
     * @param string $class
     *   The un-namespaced class name to compile to.
     * @param string $namespace
     *   the namespace for the compiled class.
     */
    public function compile(
        ProviderBuilder $listeners,
        $stream,
        string $class = 'CompiledListenerProvider',
        string $namespace = '\\Crell\\Tukio\\Compiled'
    ): void {
        fwrite($stream, $this->createPreamble($class, $namespace));

        $this->writeMainListenersList($listeners, $stream);

        $this->writeOptimizedList($listeners, $stream);

        fwrite($stream, $this->createClosing());
    }

    /**
     * Compiles a provided ProviderBuilder to an anonymous class on disk.
     *
     * The generated class requires a container instance in its constructor, which
     * because it's anonymous has a pre-defined name of $container.  That variable must
     * be in scope when the resulting file is require()ed/include()ed.  The easiest way
     * to do that is to use the loadAnonymous() method of this class, but you may also
     * do so manually.
     *
     * @param ProviderBuilder $listeners
     *    The set of listeners to compile.
     * @param resource $stream
     *    A writeable stream to which to write the compiled class.
     */
    public function compileAnonymous(ProviderBuilder $listeners, $stream): void
    {
        fwrite($stream, $this->createAnonymousPreamble());

        $this->writeMainListenersList($listeners, $stream);

        $this->writeOptimizedList($listeners, $stream);

        fwrite($stream, $this->createAnonymousClosing());
    }

    public function loadAnonymous(string $filename, ContainerInterface $container): ListenerProviderInterface
    {
        return require($filename);
    }

    /**
     * @param resource $stream
     *   A writeable stream to which to write the compiled code.
     */
    protected function writeMainListenersList(ProviderBuilder $listeners, $stream): void
    {
        fwrite($stream, $this->startMainListenersList());

        /** @var CompileableListenerEntry $listenerEntry */
        foreach ($listeners as $listenerEntry) {
            $item = $this->createEntry($listenerEntry);
            fwrite($stream, $item);
        }

        fwrite($stream, $this->endMainListenersList());
    }

    /**
     * @param resource $stream
     *   A writeable stream to which to write the compiled code.
     */
    protected function writeOptimizedList(ProviderBuilder $listeners, $stream): void
    {
        fwrite($stream, $this->startOptimizedList());

        $listenerDefs = iterator_to_array($listeners, false);

        foreach ($listeners->getOptimizedEvents() as $event) {
            $ancestors = $this->classAncestors($event);

            fwrite($stream, $this->startOptimizedEntry($event));

            $relevantListeners = array_filter($listenerDefs,
                static fn(CompileableListenerEntry $entry)
                    => in_array($entry->getProperties()['type'], $ancestors, true)
            );

            /** @var CompileableListenerEntry $listenerEntry */
            foreach ($relevantListeners as $listenerEntry) {
                $item = $this->createOptimizedEntry($listenerEntry);
                fwrite($stream, $item);
            }

            fwrite($stream, $this->endOptimizedEntry());
        }

        fwrite($stream, $this->endOptimizedList());
    }

    protected function startOptimizedEntry(string $event): string
    {
        return <<<END
    \\$event::class => [
END;
    }

    protected function endOptimizedEntry(): string
    {
        return <<<'END'
    ],
END;
    }

    protected function createOptimizedEntry(CompileableListenerEntry $listenerEntry): string
    {
        $listener = $listenerEntry->getProperties();
        $ret = match ($listener['entryType']) {
            ListenerFunctionEntry::class => "'{$listener['listener']}'",
            ListenerStaticMethodEntry::class => var_export([$listener['class'], $listener['method']], true),
            ListenerServiceEntry::class => sprintf('fn(object $event) => $this->container->get(\'%s\')->%s($event)',
                $listener['serviceName'], $listener['method']),
            default => throw new \RuntimeException(sprintf('No such listener type found in compiled container definition: %s',
                $listener['entryType'])),
        };

        return $ret . ',' . PHP_EOL;
    }

    protected function createEntry(CompileableListenerEntry $listenerEntry): string
    {
        $listener = $listenerEntry->getProperties();
        switch ($listener['entryType']) {
            case ListenerFunctionEntry::class:
                $ret = var_export(['type' => $listener['type'], 'callable' => $listener['listener']], true);
                break;
            case ListenerStaticMethodEntry::class:
                $ret = var_export(['type' => $listener['type'], 'callable' => [$listener['class'], $listener['method']]], true);
                break;
            case ListenerServiceEntry::class:
                $callable = sprintf('fn(object $event) => $this->container->get(\'%s\')->%s($event)', $listener['serviceName'], $listener['method']);
                $ret = <<<END
                [
                    'type' => '{$listener['type']}',
                    'callable' => $callable,
                ]
END;

                break;
            default:
                throw new \RuntimeException(sprintf('No such listener type found in compiled container definition: %s', $listener['entryType']));
        }

        return $ret . ',' . PHP_EOL;
    }

    protected function createPreamble(string $class, string $namespace): string
    {
        return <<<END
<?php

declare(strict_types=1);

namespace {$namespace};

use Crell\Tukio\CompiledListenerProviderBase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventInterface;

class {$class} extends CompiledListenerProviderBase
{
    public function __construct(ContainerInterface \$container)
    {
        parent::__construct(\$container);

END;
    }

    protected function createAnonymousPreamble(): string
    {
        return <<<END
<?php

declare(strict_types=1);

use Crell\Tukio\CompiledListenerProviderBase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventInterface;

return new class(\$container) extends CompiledListenerProviderBase
{
    public function __construct(ContainerInterface \$container)
    {
        parent::__construct(\$container);

END;
    }

    /**
     * Returns a list of all class and interface parents of a class.
     *
     * @param class-string $class
     * @return array<class-string>
     */
    protected function classAncestors(string $class, bool $includeClass = true): array
    {
        // These methods both return associative arrays, making + safe.
        /** @var array<class-string, class-string> $ancestors */
        $ancestors = (class_parents($class) ?: []) + (class_implements($class) ?: []);
        return $includeClass
            ? [$class => $class] + $ancestors
            : $ancestors
            ;
    }

    protected function startOptimizedList(): string
    {
        return <<<END
        \$this->optimized = [

END;
    }

    protected function endOptimizedList(): string
    {
        return <<<'END'
    ];

END;
    }

    protected function startMainListenersList(): string
    {
        return <<<END
        \$this->listeners = [

END;

    }

    protected function endMainListenersList(): string
    {
        return <<<'END'
    ];

END;
    }

    protected function createClosing(): string
    {
        return <<<'END'
    }   // Close constructor
}       // Close class

END;
    }

    protected function createAnonymousClosing(): string
    {
        return <<<'END'
    }   // Close constructor
};       // Close class

END;
    }
}
