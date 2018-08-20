<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\EventDispatcher\TaskInterface;
use Psr\EventDispatcher\TaskProcessorInterface;
use Psr\Log\LoggerInterface;

class DebugTaskProcessor implements TaskProcessorInterface
{
    /** @var TaskProcessorInterface */
    protected $processor;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * DebugTaskProcessor constructor.
     *
     * @param TaskProcessorInterface $processor
     *   The processor to wrap and for which to log errors.
     * @param LoggerInterface $logger
     *   The logger service through which to log.
     */
    public function __construct(TaskProcessorInterface $processor, LoggerInterface $logger)
    {
        $this->processor = $processor;
        $this->logger = $logger;
    }

    public function process(TaskInterface $task): TaskInterface
    {
        $this->logger->debug('Processing task of type {type}.', ['type' => get_class($task), 'task' => $task]);
        return $this->processor->process($task);
    }
}
