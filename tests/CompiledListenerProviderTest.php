<?php
declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;
use Psr\Event\Dispatcher\ListenerProviderInterface;

function listenerA(CollectingEvent $event) : void
{
    $event->add('A');
}

function listenerB(CollectingEvent $event) : void
{
    $event->add('B');
}

/**
 * @throws \Exception
 */
function noListen(EventOne $event) : void
{
    throw new \Exception('This should not be called');
}

class Listen
{
    public static function listen(CollectingEvent $event)
    {
        $event->add('C');
    }
}

class ListenService
{
    public static function listen(CollectingEvent $event)
    {
        $event->add('D');
    }
}

class CompiledEventDispatcherTest extends TestCase
{
    function testFunctionCompile()
    {
        $class = 'CompiledProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $compiler = new ProviderCompiler();

        $container = new MockContainer();
        $container->addService('D', new ListenService());

        $builder->addListener('\\Crell\\Tukio\\listenerA');
        $builder->addListener('\\Crell\\Tukio\\listenerB');
        $builder->addListener('\\Crell\\Tukio\\noListen');
        $builder->addListener([Listen::class, 'listen']);
        $builder->addListenerService('D', 'listen', CollectingEvent::class);

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
            $provider = new $compiledClassName($container);
        }
        finally {
            unlink($filename);
        }

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $result = $event->result();
        $this->assertContains('A', $result);
        $this->assertContains('B', $result);
        $this->assertContains('C', $result);
        $this->assertContains('D', $result);

        $this->assertTrue(true);
    }
}
