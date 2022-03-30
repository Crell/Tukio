<?php

declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;


class LifecycleEvent extends CollectingEvent implements CallbackEventInterface
{
    protected FakeEntity $entity;

    public function __construct(FakeEntity $entity)
    {
        $this->entity = $entity;
    }

    public function getSubject(): object
    {
        return $this->entity;
    }
}

class LoadEvent extends LifecycleEvent {}

class SaveEvent extends LifecycleEvent {}

class FakeEntity
{

    public function load(LoadEvent $event): void
    {
        $event->add('A');
    }

    public function save(SaveEvent $event): void
    {
        $event->add('B');
    }

    // @phpstan-ignore-next-line
    public function stuff(StuffEvent $event): void
    {
        // @phpstan-ignore-next-line
        $event->add('C');
    }

    public function all(LifecycleEvent $event): void
    {
        $event->add('D');
    }
}


class CallbackProviderTest extends TestCase
{

    public function test_callback(): void
    {
        $p = new CallbackProvider();

        $entity = new FakeEntity();

        $p->addCallbackMethod(LoadEvent::class, 'load');
        $p->addCallbackMethod(SaveEvent::class, 'save');
        $p->addCallbackMethod(LifecycleEvent::class, 'all');

        $event = new LoadEvent($entity);

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('AD', implode($event->result()));
    }

    public function test_non_callback_event_skips_silently(): void
    {
        $p = new CallbackProvider();

        $p->addCallbackMethod(LoadEvent::class, 'load');
        $p->addCallbackMethod(SaveEvent::class, 'save');
        $p->addCallbackMethod(LifecycleEvent::class, 'all');

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('', implode($event->result()));
    }
}
