<?php
declare(strict_types=1);

namespace Crell\Tukio;


trait ParameterDeriverTrait
{
    /**
     * Derives the class type of the first argument of a callable.
     *
     * @param callable $callable
     *   The callable for which we want the parameter type.
     * @return string
     *   The class the parameter is type hinted on.
     */
    protected function getParameterType($callable) : string
    {
        // We can't type hint $callable as it could be an array, and arrays are not callable. Sometimes. Bah, PHP.

        // This try-catch is only here to keep OCD linters happy about uncaught reflection exceptions.
        try {
            switch (true) {
                case $this->isFunctionCallable($callable):
                case $this->isClosureCallable($callable):
                    $reflect = new \ReflectionFunction($callable);
                    $params = $reflect->getParameters();
                    break;
                case $this->isClassCallable($callable):
                    $reflect = new \ReflectionClass($callable[0]);
                    $params = $reflect->getMethod($callable[1])->getParameters();
                    break;
                case $this->isObjectCallable($callable):
                    $reflect = new \ReflectionObject($callable[0]);
                    $params = $reflect->getMethod($callable[1])->getParameters();
                    break;
                default:
                    throw new \InvalidArgumentException('Not a recognized type of callable');
                    break;
            }

            $rType =$params[0]->getType();
            if ($rType == null) {
                throw new \InvalidArgumentException('Listeners must declare an object type they can accept.');
            }
            $type = $rType->getName();
        }
        catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering listener.', 0, $e);
        }

        return $type;
    }

    protected function isFunctionCallable(callable $callable) : bool
    {
        // We can't check for function_exists() because it may be included later by the time it matters.
        return is_string($callable);
    }

    protected function isClosureCallable(callable $callable) : bool
    {
        return $callable instanceof \Closure;
    }

    protected function isObjectCallable(callable $callable) : bool
    {
        return (is_array($callable) && is_object($callable[0]));
    }

    protected function isClassCallable(callable $callable) : bool
    {
        return (is_array($callable) && is_string($callable[0]) && class_exists($callable[0]));
    }
}
