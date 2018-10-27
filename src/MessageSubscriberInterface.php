<?php
declare(strict_types=1);

namespace Crell\Tukio;

interface MessageSubscriberInterface
{

    public static function registerListeners(MessageListenerProxy $proxy) : void;
}
