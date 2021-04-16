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
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @Groups({"Providers"})
 * @BeforeClassMethods({"createCompiledProvider"})
 * @AfterClassMethods({"removeCompiledProvider"})
 */
class CompiledProviderBench extends ProviderBenchBase
{
    /**
     * @var ListenerProviderInterface
     */
    protected $provider;

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
        // Now include it.  If there's a parse error PHP will throw a ParseError and PHPUnit will catch it for us.
        include static::$filename;

        $container = new MockContainer();

        $compiledClassName = static::$namespace . '\\' . static::$className;
        $this->provider = new $compiledClassName($container);
    }

    public static function fakeListener(CollectingEvent $task): void
    {
    }
}
