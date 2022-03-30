<?php

declare(strict_types=1);

namespace Crell\Tukio;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Throwable;

class InvalidTypeException extends \RuntimeException
{
    protected static string $baseMessage = 'Function does not specify a valid type';

    /**
     * @param class-string $class
     */
    public static function fromClassCallable(string $class, string $method, ?Throwable $previous = null): self
    {
        $message = static::$baseMessage;
        try {
            $reflector = new ReflectionClass($class);
            $message .= sprintf(' (%s::%s)', $reflector->getName(), $method);
        } catch (ReflectionException $e) {
            $message .= " ((unknown class)::{$method})";
        }
        return new self($message, 0, $previous);
    }

    public static function fromFunctionCallable(callable $function, ?Throwable $previous = null): self
    {
        $message = static::$baseMessage;
        if (is_string($function) || $function instanceof \Closure) {
            try {
                $reflector = new ReflectionFunction($function);
                $message .= sprintf(' (%s:%s)', $reflector->getFileName(), $reflector->getStartLine());
            } catch (ReflectionException $e) {
                // No meaningful data to add
            }
        }
        return new self($message, 0, $previous);
    }
}
