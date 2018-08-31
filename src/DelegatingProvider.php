<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\EventDispatcher\EventInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class DelegatingProvider implements ListenerProviderInterface
{

    /**
     * @var array
     *
     * An array of type to provider maps.  The keys are class name strings.
     * The values are an array of provider objects that should be called for that type.
     */
    protected $providers =[];

    /** @var ListenerProviderInterface */
    protected $defaultProvider;

    public function addProvider(ListenerProviderInterface $provider, array $types) : self
    {
        foreach ($types as $type) {
            $this->providers[$type][] = $provider;
        }

        return $this;
    }

    public function setDefaultProvider(ListenerProviderInterface $provider) : self
    {
        $this->defaultProvider = $provider;

        return $this;
    }

    public function getListenersForEvent(EventInterface $event): iterable
    {
        $found = false;
        foreach ($this->providers as $type => $providers) {
            if ($event instanceof $type) {
                /** @var ListenerProviderInterface $provider */
                foreach ($providers as $provider) {
                    $found = true;
                    yield from $provider->getListenersForEvent($event);
                }
            }
        }

        if (!$found) {
            yield from $this->defaultProvider->getListenersForEvent($event);
        }
    }
}
