<?php
declare(strict_types=1);

namespace Crell\Tukio\Workflow;

use Psr\EventDispatcher\TaskInterface;

interface WorkflowTaskInterface extends TaskInterface
{

    public function workflowName() : string;

}
