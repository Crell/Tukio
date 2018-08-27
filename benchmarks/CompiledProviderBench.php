<?php
declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\CollectingTask;
use Crell\Tukio\MockContainer;
use Crell\Tukio\ProviderBuilder;
use Crell\Tukio\ProviderCompiler;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @Groups({"Providers"})
 */
class CompiledProviderBench extends ProviderBenchBase
{
    /**
     * @var ListenerProviderInterface
     */
    protected $provider;

    /** @var string */
    protected $compiledClassName = '';

    public function setUp()
    {
        $builder = new ProviderBuilder();
        $compiler = new ProviderCompiler();
        $container = new MockContainer();

        $class = 'CompiledProvider';
        $namespace = 'Test\\Space';

        foreach(range(1, $this->numListeners) as $counter) {
            $builder->addListener([static::class, 'fakeListener']);
        }


        try {
            // Write the generated compiler out to a temp file.
            $filename = tempnam(sys_get_temp_dir(), 'compiled');
            $out = fopen($filename, 'w');
            $compiler->compile($builder, $out, $class, $namespace);
            fclose($out);

            // Now include it.  If there's a parse error PHP will throw a ParseError and PHPUnit will catch it for us.
            include($filename);

            /** @var ListenerProviderInterface $provider */
            $compiledClassName = "$namespace\\$class";
            $this->provider = new $compiledClassName($container);
        }
        finally {
            unlink($filename);
        }
    }

    public static function fakeListener(CollectingTask $task) : void
    {
    }
}
