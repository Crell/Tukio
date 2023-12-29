<?php

namespace Crell\Tukio;

class ServiceRegistrationClassNotExists extends \InvalidArgumentException
{
    public readonly string $service;

    public static function create(string $service): self
    {
        $new = new self();
        $new->service = $service;
        $msg = 'Tukio can auto-detect the type and method for a listener service only if the service ID is a valid class name. The service "%s" does not exist.  Please specify the $method and $type parameters explicitly, or check that you are using the right service name.';

        $new->message = sprintf($msg, $service);

        return $new;
    }
}
