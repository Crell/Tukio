<?php
declare(strict_types=1);

namespace Crell\Tukio;

interface TaskSubscriberInterface
{

    public static function registerListeners(ListenerProxy $proxy) : void;
}
