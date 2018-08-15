<?php
declare(strict_types=1);

namespace Crell\Tukio\Annotations\Listener;


use Crell\Tukio\CollectingTask;
use Crell\Tukio\MockContainer;
use Crell\Tukio\MockStaticListener;
use Crell\Tukio\RegisterableListenerProvider;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use PHPUnit\Framework\TestCase;

class AnnotationRegistrationTest extends TestCase
{

    public function test_static_listener_method_can_be_ordered() : void
    {
        AnnotationRegistry::registerLoader('class_exists');

        $reader = new AnnotationReader();

        $p = new RegisterableListenerProvider(new MockContainer(), $reader);

        $p->addListener([MockStaticListener::class, 'a']);
        $p->addListener([MockStaticListener::class, 'd']);
        $p->addListener([MockStaticListener::class, 'c']);
        $p->addListener([MockStaticListener::class, 'b']);

        $event = new CollectingTask();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('ABCD', implode($event->result()));
    }

}
