<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

function event_listener_one(CollectingTask $task) : void
{
    $task->add('A');
}

function event_listener_two(CollectingTask $task) : void
{
    $task->add('B');
}

function event_listener_three(CollectingTask $task) : void
{
    $task->add('C');
}

function event_listener_four(CollectingTask $task) : void
{
    $task->add('D');
}


class TestListeners
{
    public static function listenerA(CollectingTask $task) : void
    {
        $task->add('A');
    }
    public static function listenerB(CollectingTask $task) : void
    {
        $task->add('B');
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

    public function test_natural_id_for_function() : void
    {
        $p = new RegisterableListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $p->addListener("{$ns}event_listener_one", -4);
        $p->addListenerBefore("{$ns}event_listener_one", "{$ns}event_listener_two");
        $p->addListenerAfter("{$ns}event_listener_two", "{$ns}event_listener_three");
        $p->addListenerAfter("{$ns}event_listener_three", "{$ns}event_listener_four");

        $task = new CollectingTask();

        foreach ($p->getListenersForEvent($task) as $listener) {
            $listener($task);
        }

        $this->assertEquals('BACD', implode($task->result()));
    }

    public function test_natural_id_for_static_method() : void
    {
        $p = new RegisterableListenerProvider();

        $p->addListener([TestListeners::class, 'listenerA'], -4);
        $p->addListenerBefore(TestListeners::class . '::listenerA', [TestListeners::class, 'listenerB']);

        $task = new CollectingTask();

        foreach ($p->getListenersForEvent($task) as $listener) {
            $listener($task);
        }

        $this->assertEquals('BA', implode($task->result()));
    }

    public function test_natural_id_for_object_method() : void
    {
        $p = new RegisterableListenerProvider();

        $l = new TestListeners();

        $p->addListener([$l, 'listenerC'], -4);
        $p->addListenerBefore(TestListeners::class . '::listenerC', [$l, 'listenerD']);

        $task = new CollectingTask();

        foreach ($p->getListenersForEvent($task) as $listener) {
            $listener($task);
        }

        $this->assertEquals('DC', implode($task->result()));
    }

}
