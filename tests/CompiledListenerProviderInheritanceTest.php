<?php

declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;

interface EventParentInterface
{
    public function add(string $val): void;
    public function result(): array;
}

class ListenedDirectly implements EventParentInterface {
    protected array $out = [];

    public function add(string $val): void
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
    protected array $out = [];

    public function add(string $val): void
    {
        $this->out[] = $val;
    }

    public function result(): array
    {
        return $this->out;
    }
}

function inheritanceListenerA(EventParentInterface $event): void
{
    $event->add('A');
}

function inheritanceListenerB(ListenedDirectly $event): void
{
    $event->add('B');
}

function inheritanceListenerC(Subclass $event): void
{
    $event->add('C');
}


class CompiledListenerProviderInheritanceTest extends TestCase
{
    use MakeCompiledProviderTrait;

    public function test_interface_listener_catches_everything(): void
    {
        $class = __FUNCTION__;
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        $ns = __NAMESPACE__;
        $builder->addListener("{$ns}\\inheritanceListenerA");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

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

    public function test_class_listener_catches_subclass(): void
    {
        $class = __FUNCTION__;
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        $ns = __NAMESPACE__;
        $builder->addListener("{$ns}\inheritanceListenerB");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

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

    public function test_subclass_listener_catches_subclass(): void
    {
        $class = __FUNCTION__;
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        $ns = __NAMESPACE__;
        $builder->addListener("{$ns}\inheritanceListenerC");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

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
