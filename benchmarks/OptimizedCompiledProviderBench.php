<?php

declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\CollectingEvent;
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
class OptimizedCompiledProviderBench extends ProviderBenchBase
{
    /** @var string */
    protected static $filename = 'compiled_provider.php';

    /** @var string */
    protected static $className = 'CompiledProvider';

    /** @var string */
    protected static $namespace = 'Test\\Space';

    public static function createCompiledProvider(): void
    {
        $builder = new ProviderBuilder();
        $compiler = new ProviderCompiler();

        $priority = new \InfiniteIterator(new \ArrayIterator(static::$listenerPriorities));
        $priority->next();

        foreach(range(1, static::$numListeners) as $counter) {
            $builder->addListener([static::class, 'fakeListener'], $priority->current());
            $priority->next();
        }

        $builder->optimizeEvent(CollectingEvent::class);

        // Write the generated compiler out to a temp file.
        $out = fopen(static::$filename, 'w');
        $compiler->compile($builder, $out, static::$className, static::$namespace);
        fclose($out);
    }

    public static function removeCompiledProvider(): void
    {
        //unlink(static::$filename);
    }

    public function setUp(): void
    {
        include static::$filename;

        $container = new MockContainer();

        $compiledClassName = static::$namespace . '\\' . static::$className;
        $this->provider = new $compiledClassName($container);
    }
}
