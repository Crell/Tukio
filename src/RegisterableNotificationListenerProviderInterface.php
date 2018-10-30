<?php
declare(strict_types=1);

namespace Crell\Tukio;

interface RegisterableNotificationListenerProviderInterface
{
    public function addListener(callable $listener, string $type = null): void;

    public function addListenerService(string $serviceName, string $methodName, string $type): void;

    public function addSubscriber(string $class, string $serviceName): void;
}
