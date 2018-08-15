<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Doctrine\Common\Annotations\Reader;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;

class AnnotationListenerProvider implements ListenerProviderInterface, RegisterableProviderInterface
{

    /**
     * @var RegisterableProviderInterface
     */
    protected $provider;

    /**
     * @var Reader
     */
    protected $reader;

    public function __construct(RegisterableProviderInterface $provider, Reader $reader)
    {
        // @todo The provider needs to actually be a provider, too.
        $this->provider = $provider;
        $this->reader = $reader;
    }

    public function getListenersForEvent(EventInterface $event): iterable
    {
        yield from $this->provider;
    }

    public function addListener(callable $listener, $priority = 0, string $type = null): string
    {
        return $this->provider->addListener($listener, $priority, $type);
    }

    public function addListenerBefore(string $pivotId, callable $listener, string $type = null): string
    {
        return $this->provider->addListenerBefore($pivotId, $listener, $type);
    }

    public function addListenerAfter(string $pivotId, callable $listener, string $type = null): string
    {
        return $this->provider->addListenerAfter($pivotId, $listener, $type);
    }

    public function addListenerService(string $serviceName, string $methodName, string $type, $priority = 0): string
    {
        return $this->provider->addListenerService($serviceName, $methodName, $type, $priority);
    }

    public function addListenerServiceBefore(string $pivotId, string $serviceName, string $methodName, string $type): string
    {
        return $this->provider->addListenerServiceBefore($pivotId, $serviceName, $methodName, $type);
    }

    public function addListenerServiceAfter(
        string $pivotId,
        string $serviceName,
        string $methodName,
        string $type
    ): string {
        return $this->provider->addListenerServiceAfter($pivotId, $serviceName, $methodName, $type);
    }

    public function addSubscriber(string $class, string $serviceName): void
    {
        // TODO: Implement addSubscriber() method.
    }

}
