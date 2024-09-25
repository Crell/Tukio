<?php

declare(strict_types=1);

namespace Crell\Tukio\Fakes;

use Psr\Log\AbstractLogger;

class MockLogger extends AbstractLogger
{

    /**
     * @var array<mixed>
     */
    public array $messages = [];

    /**
     * @param array<string, string> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->messages[$level][] = [
            'message' => $message,
            'context' => $context,
        ];
    }
}
