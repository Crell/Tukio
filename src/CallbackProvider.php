<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;

class CallbackProvider implements ListenerProviderInterface
{
    /** @var array */
    protected $callbacks = [];

    public function getListenersForEvent(EventInterface $event): iterable
    {
        if (!$event instanceof CallbackTaskInterface) {
            return [];
        }

        $subject = $event->getSubject();

        foreach ($this->callbacks as $type => $callbacks) {
            if ($event instanceof $type) {
                foreach ($callbacks as $callback) {
                    if (method_exists($subject, $callback)) {
                        yield [$subject, $callback];
                    }
                }
            }
        }
    }

    public function addCallbackMethod(string $type, string $method) : self
    {
        $this->callbacks[$type][] = $method;
        return $this;
    }

}
