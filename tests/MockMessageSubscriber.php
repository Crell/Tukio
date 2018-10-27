<?php
declare(strict_types=1);

namespace Crell\Tukio;


class MockMessageSubscriber implements MessageSubscriberInterface
{
    public $results = [];

    public function onA(MessageOne $event) : void
    {
        $this->results[] = 'A';
    }
    public function onB(MessageOne $event) : void
    {
        $this->results[] = 'B';
    }
    public function onC(MessageOne $event) : void
    {
        $this->results[] = 'C';
    }
    public function onD(MessageOne $event) : void
    {
        $this->results[] = 'D';
    }
    public function onE(MessageOne $event) : void
    {
        $this->results[] = 'E';
    }

    public function notNormalName(MessageOne $message) : void
    {
        $this->results[] = 'F';
    }

    public function onG(NoMessage $message) : void
    {
        $this->results[] = 'G';
    }

    public function ignoredMethodThatDoesNothing() : void
    {
        throw new \Exception('What are you doing here?');
    }

    public static function registerListeners(MessageListenerProxy $proxy): void
    {
        $proxy->addListener('onA');
        $proxy->addListener('onB');
        $proxy->addListener('onC');
        $proxy->addListener('onD');
        // Don't register E.  It should self-register by reflection.
        $proxy->addListener('notNormalName');
        $proxy->addListener('onG');
    }
}

