<?php

declare(strict_types=1);

namespace Crell\Tukio;


use Crell\Tukio\Events\CollectingEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FakeEntity
{
    public function load(Events\LoadEvent $event): void
    {
        $event->add('A');
    }

    public function save(Events\SaveEvent $event): void
    {
        $event->add('B');
    }

    // @phpstan-ignore-next-line
    public function stuff(StuffEvent $event): void
    {
        // @phpstan-ignore-next-line
        $event->add('C');
    }

    public function all(Events\LifecycleEvent $event): void
    {
        $event->add('D');
    }
}

class CallbackProviderTest extends TestCase
{

    #[Test]
    public function callback_provider(): void
    {
        $p = new CallbackProvider();

        $entity = new FakeEntity();

        $p->addCallbackMethod(Events\LoadEvent::class, 'load');
        $p->addCallbackMethod(Events\SaveEvent::class, 'save');
        $p->addCallbackMethod(Events\LifecycleEvent::class, 'all');

        $event = new Events\LoadEvent($entity);

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('AD', implode($event->result()));
    }

    #[Test]
    public function non_callback_event_skips_silently(): void
    {
        $p = new CallbackProvider();

        $p->addCallbackMethod(Events\LoadEvent::class, 'load');
        $p->addCallbackMethod(Events\SaveEvent::class, 'save');
        $p->addCallbackMethod(Events\LifecycleEvent::class, 'all');

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('', implode($event->result()));
    }
}
