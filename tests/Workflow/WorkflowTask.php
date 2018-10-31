<?php
declare(strict_types=1);

namespace Crell\Tukio\Workflow;

use Crell\Tukio\CollectingTask;

class WorkflowTask extends CollectingTask implements WorkflowTaskInterface
{
    /** @var string */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function workflowName(): string
    {
        return $this->name;
    }
}
