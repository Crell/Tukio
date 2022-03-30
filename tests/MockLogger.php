<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Log\AbstractLogger;

class MockLogger extends AbstractLogger {

    /**
     * @var array<mixed>
     */
    public array $messages = [];

    /**
     * @param array<string, string> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->messages[$level][] = [
            'message' => $message,
            'context' => $context,
        ];
    }
}
