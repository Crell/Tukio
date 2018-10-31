<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\EventDispatcher\EventInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * A Delegating provider.
 *
 * The delegating provider allows for selected types of event to be handled by dedicated
 * sub-providers, which if used will block the use of a default sub-provider.  That is,
 * certain high-frequency event types (mainly some Tasks) can be handled by dedicated
 * providers and then skip the normal lookup process of the default provider.  That can
 * provide a performance benefit if certain tasks are triggered many dozens of times
 * or more.
 *
 * Note: The presence of a sub-provider that wants to intercept a given type of event
 * will be sufficient to block the default from firing, even if it has no applicable
 * listeners.
 */
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
