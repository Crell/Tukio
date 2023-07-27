<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\CompileableListenerEntryInterface;

class ProviderCompiler
{
    /**
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

    protected function writeMainListenersList(ProviderBuilder $listeners, $stream): void
    {
        fwrite($stream, $this->startMainListenersList());

        /** @var CompileableListenerEntryInterface $listenerEntry */
        foreach ($listeners as $listenerEntry) {
            $item = $this->createEntry($listenerEntry);
            fwrite($stream, $item);
        }

        fwrite($stream, $this->endMainListenersList());
    }

    protected function writeOptimizedList(ProviderBuilder $listeners, $stream): void
    {
        fwrite($stream, $this->startOptimizedList());

        $listenerDefs = iterator_to_array($listeners);

        foreach ($listeners->optimizedEvents() as $event) {
            $ancestors = $this->classAncestors($event);

            fwrite($stream, $this->startOptimizedEntry($event));

            $relevantListeners = array_filter($listenerDefs, fn(CompileableListenerEntryInterface $entry) => in_array($entry->getProperties()['type'], $ancestors));

            /** @var CompileableListenerEntryInterface $listenerEntry */
            foreach ($relevantListeners as $listenerEntry) {
                $item = $this->createEntry($listenerEntry);
                fwrite($stream, $item);
            }

            fwrite($stream, $this->endOptimizedEntry());
        }

        fwrite($stream, $this->endOptimizedList());
    }

    protected function startOptimizedEntry(string $event): string
    {
        return <<<END
    $event::class => [
END;
    }

    protected function endOptimizedEntry(): string
    {
        return <<<'END'
    ],
END;
    }

    /**
     * Returns a list of all class and interface parents of a class.
     *
     * @return array<class-string>
     */
    protected function classAncestors(string $class, bool $includeClass = true): array
    {
        // These methods both return associative arrays, making + safe.
        $ancestors = class_parents($class) + class_implements($class);
        return $includeClass
            ? [$class => $class] + $ancestors
            : $ancestors
            ;
    }

    protected function startOptimizedList(): string
    {
        return <<<END
  protected const OPTIMIZED = [

END;
    }

    protected function endOptimizedList(): string
    {
        return <<<'END'
    ];

END;
    }

    protected function createEntry(CompileableListenerEntryInterface $listenerEntry): string
    {
        return var_export($listenerEntry->getProperties(), true) . ',' . PHP_EOL;
    }

    protected function createPreamble(string $class, string $namespace): string
    {
        return <<<END
<?php

declare(strict_types=1);

namespace {$namespace};

use Crell\Tukio\CompiledListenerProviderBase;
use Psr\EventDispatcher\EventInterface;

class {$class} extends CompiledListenerProviderBase
{

END;
    }

    protected function startMainListenersList(): string
    {
        return <<<END
  protected const LISTENERS = [

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
}

END;
    }
}
