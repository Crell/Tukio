<?php
declare(strict_types=1);

namespace Crell\Tukio;

use \ReflectionClass;
use \ReflectionFunction;
use \ReflectionException;
use \Throwable;

class InvalidTypeException extends \RuntimeException
{
    protected static $baseMessage = 'Function does not specify a valid type';

    public static function fromClassCallable($class, string $method, ?Throwable $previous = null)
    {
        $message = static::$baseMessage;
        try {
            $reflector = new ReflectionClass($class);
            $message .= " (" . $reflector->getName() . "::$method)";
        } catch (ReflectionException $e) {
            $message .= " ((unknown class)::$method)";
        }
        return new static($message, 0, $previous);
    }
    
    public static function fromFunctionCallable(callable $function, ?Throwable $previous = null)
    {
        $message = static::$baseMessage;
        if (is_string($function) || $function instanceof \Closure) {
            try {
                $reflector = new ReflectionFunction($function);
                $message .= " (" . $reflector->getFileName() . ":" . $reflector->getStartLine() . ")";
            } catch (ReflectionException $e) {
                // No meaningful data to add
            }
        }
        return new static($message, 0, $previous);
    }
}
