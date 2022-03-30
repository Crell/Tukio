<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Fig\EventDispatcher\ParameterDeriverTrait;

/**
 * For internal use only.
 *
 * @internal
 */
trait ProviderUtilities
{
    use ParameterDeriverTrait;

    protected function getAttributes(callable $listener): array
    {
        // Bail out < PHP 8.0.
        if (!class_exists('ReflectionAttribute', false)) {
            return [];
        }

        $ref = null;

        if ($this->isFunctionCallable($listener)) {
            $ref = new \ReflectionFunction($listener);
        } elseif ($this->isClassCallable($listener)) {
            // PHPStan says you cannot use array destructuring on a callable, but you can
            // if you know that it's an array (which in context we do).
            // @phpstan-ignore-next-line
            [$class, $method] = $listener;
            $ref = (new \ReflectionClass($class))->getMethod($method);
        } elseif ($this->isObjectCallable($listener)) {
            // PHPStan says you cannot use array destructuring on a callable, but you can
            // if you know that it's an array (which in context we do).
            // @phpstan-ignore-next-line
            [$class, $method] = $listener;
            $ref = (new \ReflectionObject($class))->getMethod($method);
        }

        if (!$ref) {
            return [];
        }

        $attribs = $ref->getAttributes(ListenerAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

        return array_map(static function(\ReflectionAttribute $attrib) {
            return $attrib->newInstance();
        }, $attribs);

        // Replace the above with this line once we require PHP 7.4.
        //return array_map(fn(\ReflectionAttribute $attrib) => $attrib->newInstance(), $attribs);
    }

    /**
     * Tries to get the type of a callable listener.
     *
     * If unable, throws an exception with information about the listener whose type could not be fetched.
     *
     * @param callable $listener
     *   The callable from which to extract a type.
     *
     * @return string
     *   The type of the first argument.
     */
    protected function getType(callable $listener): string
    {
        try {
            $type = $this->getParameterType($listener);
        } catch (\InvalidArgumentException $exception) {
            if ($this->isClassCallable($listener) || $this->isObjectCallable($listener)) {
                throw InvalidTypeException::fromClassCallable($listener[0], $listener[1], $exception);
            }
            if ($this->isFunctionCallable($listener) || $this->isClosureCallable($listener)) {
                throw InvalidTypeException::fromFunctionCallable($listener, $exception);
            }
            throw new InvalidTypeException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return $type;
    }

    /**
     * Derives a predictable ID from the listener if possible.
     *
     * It's OK for this method to return null, as OrderedCollection will
     * generate a random ID if necessary.  It will also handle duplicates
     * for us.  This method is just a suggestion.
     *
     * @param callable $listener
     *   The listener for which to derive an ID.
     *
     * @return string|null
     *   The derived ID if possible or null if no reasonable ID could be derived.
     */
    protected function getListenerId(callable $listener): ?string
    {
        // The methods called in this method are from an external trait, and
        // its docblock is a bit buggy.  Just ignore that on our end until
        // it's fixed in the util package.
        // @phpstan-ignore-next-line
        if ($this->isFunctionCallable($listener)) {
            // Function callables are strings, so use that directly.
            // @phpstan-ignore-next-line
            return (string)$listener;
        }
        // @phpstan-ignore-next-line
        if ($this->isClassCallable($listener)) {
            return $listener[0] . '::' . $listener[1];
        }
        // @phpstan-ignore-next-line
        if (is_array($listener) && is_object($listener[0])) {
            return get_class($listener[0]) . '::' . $listener[1];
        }

        // Anything else we can't derive an ID for logically.
        return null;
    }

    /**
     * Determines if a callable represents a function.
     *
     * Or at least a reasonable approximation, since a function name may not be defined yet.
     *
     * @return bool
     *  True if the callable represents a function, false otherwise.
     */
    protected function isFunctionCallable(callable $callable): bool
    {
        // We can't check for function_exists() because it may be included later by the time it matters.
        return is_string($callable);
    }

    /**
     * Determines if a callable represents a method on an object.
     *
     * @return bool
     *  True if the callable represents a method object, false otherwise.
     */
    protected function isObjectCallable(callable $callable): bool
    {
        return is_array($callable) && is_object($callable[0]);
    }

    /**
     * Determines if a callable represents a closure/anonymous function.
     *
     * @return bool
     *  True if the callable represents a closure object, false otherwise.
     */
    protected function isClosureCallable(callable $callable): bool
    {
        return $callable instanceof \Closure;
    }
}
