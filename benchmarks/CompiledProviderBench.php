<?php

declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\CollectingEvent;
use Crell\Tukio\DummyEvent;
use Crell\Tukio\MockContainer;
use Crell\Tukio\ProviderBuilder;
use Crell\Tukio\ProviderCompiler;
use PhpBench\Benchmark\Metadata\Annotations\AfterClassMethods;
use PhpBench\Benchmark\Metadata\Annotations\BeforeClassMethods;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\OutputTimeUnit;
use PhpBench\Benchmark\Metadata\Annotations\RetryThreshold;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;

/**
 * @Groups({"Providers"})
 * @BeforeClassMethods({"createCompiledProvider"})
 * @AfterClassMethods({"removeCompiledProvider"})
 */
class CompiledProviderBench extends ProviderBenchBase
{
    protected static string $filename = 'compiled_provider.php';

    protected static string $className = 'CompiledProvider';

    protected static string $namespace = 'Test\\Space';

    protected static array $optimizeClasses = [];

    public static function createCompiledProvider(): void
    {
        $builder = new ProviderBuilder();
        $compiler = new ProviderCompiler();

        $priority = new \InfiniteIterator(new \ArrayIterator(static::$listenerPriorities));
        $priority->next();

        foreach(range(1, ceil(static::$numListeners/2)) as $counter) {
            $builder->addListener([static::class, 'fakeListener'], $priority->current());
            $builder->addListenerService('Foo', 'bar', DummyEvent::class, $priority->current());
            $priority->next();
        }

        foreach (static::$optimizeClasses as $class) {
            $builder->optimizeEvent($class);
        }

        // Write the generated compiler out to a temp file.
        $out = fopen(static::$filename, 'w');
        $compiler->compile($builder, $out, static::$className, static::$namespace);
        fclose($out);
    }

    public static function removeCompiledProvider(): void
    {
        unlink(static::$filename);
    }

    public function setUp(): void
    {
        include static::$filename;

        $container = new MockContainer();

        $compiledClassName = static::$namespace . '\\' . static::$className;
        $this->provider = new $compiledClassName($container);
    }
}
