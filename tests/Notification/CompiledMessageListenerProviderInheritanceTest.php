<?php
declare(strict_types=1);

namespace Crell\Tukio\Notification;

use Crell\Tukio\MakeCompiledProviderTrait;
use Crell\Tukio\MockContainer;
use Crell\Tukio\NotificationProviderBuilder;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventInterface;

interface EventParentInterface extends EventInterface
{
    public function add(string $val) : void;
    public function result() : array;
}

class ListenedDirectly implements EventParentInterface {
    protected $out = [];

    public function add(string $val) : void
    {
        $this->out[] = $val;
    }

    public function result() : array
    {
        return $this->out;
    }
}

class Subclass extends ListenedDirectly {}

class NotListenedDirectly implements EventParentInterface {
    protected $out = [];

    public function add(string $val) : void
    {
        $this->out[] = $val;
    }

    public function result() : array
    {
        return $this->out;
    }
}

function inheritanceListenerA(EventParentInterface $event) : void
{
    $event->add('A');
}

function inheritanceListenerB(ListenedDirectly $event) : void
{
    $event->add('B');
}

function inheritanceListenerC(Subclass $event) : void
{
    $event->add('C');
}


class CompiledMessageListenerProviderInheritanceTest extends TestCase
{
    use MakeCompiledProviderTrait;

    protected $compiledNamespace = 'Test\\Notification';

    public function test_interface_listener_catches_everything() : void
    {
        $class = __FUNCTION__;

        $builder = new NotificationProviderBuilder();
        $container = new MockContainer();

        $ns = __NAMESPACE__;
        $builder->addListener("{$ns}\\inheritanceListenerA");

        $provider = $this->makeProvider($builder, $container, $class, $this->compiledNamespace);

        $tests = [
            ListenedDirectly::class => 'A',
            Subclass::class => 'A',
            NotListenedDirectly::class => 'A',
        ];

        foreach ($tests as $class => $result) {
            /** @var EventParentInterface $event */
            $event = new $class();
            foreach ($provider->getListenersForEvent($event) as $listener) {
                $listener($event);
            }
            $this->assertEquals($result, implode($event->result()));
        }
    }

    public function test_class_listener_catches_subclass() : void
    {
        $class = __FUNCTION__;

        $builder = new NotificationProviderBuilder();
        $container = new MockContainer();

        $ns = __NAMESPACE__;
        $builder->addListener("{$ns}\inheritanceListenerB");

        $provider = $this->makeProvider($builder, $container, $class, $this->compiledNamespace);

        $tests = [
            ListenedDirectly::class => 'B',
            Subclass::class => 'B',
            NotListenedDirectly::class => '',
        ];

        foreach ($tests as $class => $result) {
            /** @var EventParentInterface $event */
            $event = new $class();
            foreach ($provider->getListenersForEvent($event) as $listener) {
                $listener($event);
            }
            $this->assertEquals($result, implode($event->result()));
        }
    }

    public function test_subclass_listener_catches_subclass() : void
    {
        $class = __FUNCTION__;

        $builder = new NotificationProviderBuilder();
        $container = new MockContainer();

        $ns = __NAMESPACE__;
        $builder->addListener("{$ns}\inheritanceListenerC");

        $provider = $this->makeProvider($builder, $container, $class, $this->compiledNamespace);

        $tests = [
            ListenedDirectly::class => '',
            Subclass::class => 'C',
            NotListenedDirectly::class => '',
        ];

        foreach ($tests as $class => $result) {
            /** @var EventParentInterface $event */
            $event = new $class();
            foreach ($provider->getListenersForEvent($event) as $listener) {
                $listener($event);
            }
            $this->assertEquals($result, implode($event->result()));
        }
    }
}
