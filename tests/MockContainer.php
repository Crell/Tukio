<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class MockContainer implements ContainerInterface
{

    protected $services = [];

    public function addService(string $id, $service)
    {
        $this->services[$id] = $service;
    }

    public function get($id)
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }
        throw new class extends \Exception implements NotFoundExceptionInterface {};
    }

    public function has($id): bool
    {
        return isset($this->services[$id]);
    }

}
