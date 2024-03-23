<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Fakes\EventParentInterface;
use Crell\Tukio\Fakes\MockContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

function inheritanceListenerA(Fakes\EventParentInterface $event): void
{
    $event->add('A');
}

function inheritanceListenerB(Fakes\ListenedDirectly $event): void
{
    $event->add('B');
}

function inheritanceListenerC(Fakes\Subclass $event): void
{
    $event->add('C');
}

class CompiledListenerProviderInheritanceTest extends TestCase
{
    use MakeCompiledProviderTrait;

    #[Test]
    public function interface_listener_catches_everything(): void
    {
        $class = __FUNCTION__;
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        $ns = __NAMESPACE__;
        $builder->addListener("{$ns}\\inheritanceListenerA");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $tests = [
            Fakes\ListenedDirectly::class => 'A',
            Fakes\Subclass::class => 'A',
            Fakes\NotListenedDirectly::class => 'A',
        ];

        foreach ($tests as $class => $result) {
            /** @var \Crell\Tukio\Fakes\EventParentInterface $event */
            $event = new $class();
            foreach ($provider->getListenersForEvent($event) as $listener) {
                $listener($event);
            }
            self::assertEquals($result, implode($event->result()));
        }
    }

    #[Test]
    public function class_listener_catches_subclass(): void
    {
        $class = __FUNCTION__;
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        $ns = __NAMESPACE__;
        $builder->addListener("{$ns}\inheritanceListenerB");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $tests = [
            Fakes\ListenedDirectly::class => 'B',
            Fakes\Subclass::class => 'B',
            Fakes\NotListenedDirectly::class => '',
        ];

        foreach ($tests as $class => $result) {
            /** @var \Crell\Tukio\Fakes\EventParentInterface $event */
            $event = new $class();
            foreach ($provider->getListenersForEvent($event) as $listener) {
                $listener($event);
            }
            self::assertEquals($result, implode($event->result()));
        }
    }

    #[Test]
    public function subclass_listener_catches_subclass(): void
    {
        $class = __FUNCTION__;
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        $ns = __NAMESPACE__;
        $builder->addListener("{$ns}\inheritanceListenerC");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $tests = [
            Fakes\ListenedDirectly::class => '',
            Fakes\Subclass::class => 'C',
            Fakes\NotListenedDirectly::class => '',
        ];

        foreach ($tests as $class => $result) {
            /** @var \Crell\Tukio\Fakes\EventParentInterface $event */
            $event = new $class();
            foreach ($provider->getListenersForEvent($event) as $listener) {
                $listener($event);
            }
            self::assertEquals($result, implode($event->result()));
        }
    }

}
