<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\EventDispatcher\EventInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * A compound provider encapsulates multiple other providers and concatenates their responses.
 *
 * The main use case for this class is to compose a fast compiled provider followed by a runtime-editable
 * provider, allowing maximum flexibility while still achieving high performance.
 */
class CompoundProvider implements ListenerProviderInterface
{
    /**
     * @var array
     */
    protected $providers = [];

    public function getListenersForEvent(EventInterface $event): iterable
    {
        /** @var ListenerProviderInterface $provider */
        foreach ($this->providers as $provider) {
            yield from $provider->getListenersForEvent($event);
        }
    }

    /**
     * Enqueues a listener provider to this set.
     *
     * @param ListenerProviderInterface $provider
     *   The provider to add.
     * @return CompoundProvider
     *   The called object.
     */
    public function addProvider(ListenerProviderInterface $provider) : self
    {
        $this->providers[] = $provider;
        return $this;
    }
}
