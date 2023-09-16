<?php

declare(strict_types=1);

namespace Crell\Tukio;

/**
 * This class fantasizes of being an ADT Enum. Convert it as soon as possible.
 */
abstract class Order
{
    public static function Priority(int $priority): static
    {
        return new OrderPriority(priority: $priority);
    }

    public static function Before(string $before): static
    {
        return new OrderBefore(before: $before);
    }

    public static function After(string $after): static
    {
        return new OrderAfter(after: $after);
    }
}

final class OrderPriority extends Order
{
    public function __construct(public readonly int $priority) {}
}

final class OrderBefore extends Order
{
    public function __construct(public readonly string $before) {}
}

final class OrderAfter extends Order
{
    public function __construct(public readonly string $after) {}
}
