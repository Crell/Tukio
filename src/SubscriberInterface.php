<?php

declare(strict_types=1);

namespace Crell\Tukio;

/**
 * @deprecated
 */
interface SubscriberInterface
{
    public static function registerListeners(ListenerProxy $proxy): void;
}
