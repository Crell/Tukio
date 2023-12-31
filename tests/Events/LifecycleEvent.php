<?php

namespace Crell\Tukio\Events;

use Crell\Tukio\CallbackEventInterface;
use Crell\Tukio\FakeEntity;

class LifecycleEvent extends CollectingEvent implements CallbackEventInterface
{
    protected FakeEntity $entity;

    public function __construct(FakeEntity $entity)
    {
        $this->entity = $entity;
    }

    public function getSubject(): object
    {
        return $this->entity;
    }
}
