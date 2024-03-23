<?php

namespace Crell\Tukio;

class ServiceRegistrationTooManyMethods extends \InvalidArgumentException
{
    public readonly string $service;

    public static function create(string $service): self
    {
        $new = new self();
        $new->service = $service;
        $msg = 'Tukio can auto-detect a single method on a listener service, or use one named __invoke().  The "%s" service has too many methods not named __invoke().  Please check your class or use a subscriber.';

        $new->message = sprintf($msg, $service);

        return $new;
    }
}
