<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Crell\Tukio\Annotations\Listener;

class MockStaticListener
{
    /**
     * @param CollectingTask $task
     *
     * @Listener(id="a")
     */
    public static function a(CollectingTask $task): void
    {
        $task->add('A');
    }

    /**
     * @param CollectingTask $task
     *
     * @Listener(id="b", after="a")
     */
    public static function b(CollectingTask $task): void
    {
        $task->add('B');
    }

    /**
     * @param CollectingTask $task
     *
     * @Listener(id="c", before="d")
     */
    public static function c(CollectingTask $task): void
    {
        $task->add('C');
    }

    /**
     * @param CollectingTask $task
     *
     * @Listener(id="d", priority="10")
     */
    public static function d(CollectingTask $task): void
    {
        $task->add('D');
    }
}
