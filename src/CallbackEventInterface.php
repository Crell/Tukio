<?php
declare(strict_types=1);

namespace Crell\Tukio;

/**
 * An Event that carries an object that has its own event callbacks.
 */
interface CallbackEventInterface
{

    /**
     * Returns the subject of the event.
     *
     * This is the object on which callback methods will be called, if applicable.
     *
     * @return object
     */
    public function getSubject() : object;
}
