<?php
declare(strict_types=1);

namespace Crell\Tukio;

use \ReflectionClass;
use \ReflectionFunction;
use \ReflectionException;

class InvalidTypeException extends \RuntimeException
{
    protected $message = 'Function does not specify a valid type';

    public function setMessageFromClass($class, string $method)
    {
        try {
            $reflector = new ReflectionClass($class);
            $this->message .= " (".$reflector->getName()."::$method)";
        } catch (ReflectionException $e) {
            $this->message .= " ((unknown class)::$method)";
        }
        return $this;
    }

    public function setMessageFromFunction(callable $function)
    {
        if (is_string($function) || $function instanceof \Closure) {
            try {
                $reflector = new ReflectionFunction($function);
                $this->message .= " (".$reflector->getFileName().":".$reflector->getStartLine().")";
            } catch (ReflectionException $e) {
                // No meaningful data to add
            }
        }
        return $this;
    }
}
