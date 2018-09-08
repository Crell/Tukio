<?php
declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

function listenerA(CollectingTask $event) : void
{
    $event->add('A');
}

function listenerB(CollectingTask $event) : void
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
    public static function listen(CollectingTask $event)
    {
        $event->add('C');
    }
}

class ListenService
{
    public static function listen(CollectingTask $event)
    {
        $event->add('D');
    }
}

class CompiledEventDispatcherTest extends TestCase
{
    function test_compiled_provider_triggers_in_order()
    {
        $class = 'CompiledProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();

        $container = new MockContainer();
        $container->addService('D', new ListenService());

        $builder->addListener('\\Crell\\Tukio\\listenerA');
        $builder->addListener('\\Crell\\Tukio\\listenerB');
        $builder->addListener('\\Crell\\Tukio\\noListen');
        $builder->addListener([Listen::class, 'listen']);
        $builder->addListenerService('D', 'listen', CollectingTask::class);

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingTask();
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

    public function test_add_subscriber()
    {
        // This test is parallel to and uses the same mock subscriber as
        // RegisterableListenerProviderServiceTest::test_add_subscriber().
        // Thus if both work, it means the same subscriber works the same
        // transparently in both compiled and non-compiled versions.

        $class = 'SubscriberProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();
        $subscriber = new MockSubscriber();

        $container->addService('subscriber', $subscriber);
        $builder->addSubscriber(MockSubscriber::class, 'subscriber');

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingTask();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('BCAEDF', implode($event->result()));
    }

    public function test_explict_id_on_compiled_provider() : void
    {
        $class = 'ExplicitIdProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $builder->addListener("{$ns}event_listener_one", -4);
        $builder->addListenerBefore("{$ns}event_listener_one", "{$ns}event_listener_two");
        $builder->addListenerAfter("{$ns}event_listener_two", "{$ns}event_listener_three");
        $builder->addListenerAfter("{$ns}event_listener_three", "{$ns}event_listener_four");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $task = new CollectingTask();
        foreach ($provider->getListenersForEvent($task) as $listener) {
            $listener($task);
        }

        $this->assertEquals('BACD', implode($task->result()));
    }


    /**
     * Converts a builder into a compiled container.
     *
     * Technically this is the core of every test in this class.  However, the build process
     * is identical in all cases so there's little point in repeating the code.
     *
     * @param ProviderBuilder $builder
     *   A builder, ready to compile.
     * @param ContainerInterface $container
     *   A container to provide to the built provider.
     * @param string $class
     *   The class name of the provider to generate.
     * @param string $namespace
     *   The namespace of the provider to generate.
     * @return ListenerProviderInterface
     *   The compiled provider.
     */
    protected function makeProvider(ProviderBuilder $builder, ContainerInterface $container, string $class, string $namespace) : ListenerProviderInterface
    {
        try {
            $compiler = new ProviderCompiler();

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

        return $provider;
    }
}
