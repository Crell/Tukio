<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\EventDispatcher\TaskInterface;

/**
 * A Task that carries an object that has its own event callbacks.
 */
interface CallbackTaskInterface extends TaskInterface
{

    /**
     * Returns the subject of the event.
     *
     * This is the object on which callback methods will be called, if applicable.
     *
     * @return object
     */
    public function getSubject();
}
