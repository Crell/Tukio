<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;


class LifecycleTask extends CollectingTask implements CallbackTaskInterface
{
    protected $entity;

    public function __construct(FakeEntity $entity)
    {
        $this->entity = $entity;
    }

    public function getSubject()
    {
        return $this->entity;
    }
}

class LoadTask extends LifecycleTask {}

class SaveTask extends LifecycleTask {}

class FakeEntity
{

    public function load(LoadTask $task) : void
    {
        $task->add('A');
    }

    public function save(SaveTask $task) : void
    {
        $task->add('B');
    }

    public function stuff(StuffTask $task) : void
    {
        $task->add('C');
    }

    public function all(LifecycleTask $task) : void
    {
        $task->add('D');
    }
}


class CallbackProviderTest extends TestCase
{

    public function test_callback() : void
    {
        $p = new CallbackProvider();

        $entity = new FakeEntity();

        $p->addCallbackMethod(LoadTask::class, 'load');
        $p->addCallbackMethod(SaveTask::class, 'save');
        $p->addCallbackMethod(LifecycleTask::class, 'all');

        $task = new LoadTask($entity);

        foreach ($p->getListenersForEvent($task) as $listener) {
            $listener($task);
        }

        $this->assertEquals('AD', implode($task->result()));
    }

    public function test_non_callback_task_skips_silently() : void
    {
        $p = new CallbackProvider();

        $p->addCallbackMethod(LoadTask::class, 'load');
        $p->addCallbackMethod(SaveTask::class, 'save');
        $p->addCallbackMethod(LifecycleTask::class, 'all');

        $task = new CollectingTask();

        foreach ($p->getListenersForEvent($task) as $listener) {
            $listener($task);
        }

        $this->assertEquals('', implode($task->result()));
    }
}
