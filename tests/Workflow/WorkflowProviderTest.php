<?php
declare(strict_types=1);

namespace Crell\Tukio\Workflow;

use Crell\Tukio\CollectingTask;
use PHPUnit\Framework\TestCase;

class WorkflowProviderTest extends TestCase
{

    public function test_non_workflow_events_ignored() : void
    {
        $p = new WorkflowProvider();

        $p->addListener(function (WorkflowStartTask $task) {
            $task->add('A');
        }, 'bob');

        $event = new CollectingTask();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEmpty($event->result());
    }

    public function test_workflow_event_called_for_own_name_only() : void
    {
        $p = new WorkflowProvider();

        $p->addListener(function (WorkflowStartTask $task) {
            $task->add('A');
        }, 'bob');

        $p->addListener(function (WorkflowEndTask $task) {
            $task->add('B');
        }, 'bob');

        $p->addListener(function (WorkflowTask $task) {
            $task->add('C');
        }, 'bob');

        $p->addListener(function (WorkflowStartTask $task) {
            $task->add('D');
        }, 'anita');

        $event = new WorkflowStartTask('bob');

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('AC', implode($event->result()));
    }
}
