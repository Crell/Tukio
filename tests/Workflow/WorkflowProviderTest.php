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

        // This has the right workflow name and event type.
        $p->addListener(function (WorkflowStartTask $task) {
            $task->add('A');
        }, 'bob');

        // This has the wrong workflow type.
        $p->addListener(function (WorkflowEndTask $task) {
            $task->add('B');
        }, 'bob');

        // This matches the parent, so would be called for both WorkflowStart and WorkflowEnd.
        $p->addListener(function (WorkflowTask $task) {
            $task->add('C');
        }, 'bob');

        // This is the right type but the wrong workflow name, so it won't be called.
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
