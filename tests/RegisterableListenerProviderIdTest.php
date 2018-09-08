<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

function event_listener_one(CollectingTask $event) : void
{
    $event->add('A');
}

function event_listener_two(CollectingTask $event) : void
{
    $event->add('B');
}

class TestListeners
{
    public static function listenerA(CollectingTask $event) : void
    {
        $event->add('A');
    }
    public static function listenerB(CollectingTask $event) : void
    {
        $event->add('B');
    }

    public function listenerC(CollectingTask $task) : void
    {
        $task->add('C');
    }

    public function listenerD(CollectingTask $task) : void
    {
        $task->add('D');
    }
}

class RegisterableListenerProviderIdTest extends TestCase
{

    public function test_explict_id_for_function() : void
    {
        $p = new RegisterableListenerProvider();

        $p->addListener('\\Crell\\Tukio\\event_listener_one', -4);
        $p->addListenerBefore('\\Crell\\Tukio\\event_listener_one', '\\Crell\\Tukio\\event_listener_two');

        $event = new CollectingTask();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('BA', implode($event->result()));
    }

    public function test_explict_id_for_static_method() : void
    {
        $p = new RegisterableListenerProvider();

        $p->addListener([TestListeners::class, 'listenerA'], -4);
        $p->addListenerBefore(TestListeners::class . '::listenerA', [TestListeners::class, 'listenerB']);

        $event = new CollectingTask();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('BA', implode($event->result()));
    }

    public function test_explict_id_for_object_method() : void
    {
        $p = new RegisterableListenerProvider();

        $l = new TestListeners();

        $p->addListener([$l, 'listenerC'], -4);
        $p->addListenerBefore(TestListeners::class . '::listenerC', [$l, 'listenerD']);

        $event = new CollectingTask();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('DC', implode($event->result()));
    }

}
