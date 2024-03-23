<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;
use Crell\AttributeUtils\Finalizable;
use Crell\AttributeUtils\FromReflectionMethod;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\ParseMethods;
use Crell\AttributeUtils\ParseStaticMethods;
use Crell\AttributeUtils\ReadsClass;

/**
 * The main attribute to customize a listener.
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Listener implements ListenerAttribute, HasSubAttributes, ParseMethods, ReadsClass, Finalizable, FromReflectionMethod, ParseStaticMethods
{
    /**
     * @var Listener[]
     *
     * This is only used by the class-level attribute.  When used on a method level it is ignored.
     */
    public readonly array $methods;

    /**
     * @var Listener[]
     *
     * This is only used by the class-level attribute.  When used on a method level it is ignored.
     */
    public readonly array $staticMethods;


    /** @var string[]  */
    public array $before = [];

    /** @var string[] */
    public array $after = [];
    public ?int $priority = null;

    public readonly bool $hasDefinition;

    /**
     * This is only meaningful on the method attribute.
     */
    public readonly int $paramCount;

    /**
     * @param ?string $id
     *     The identifier by which this listener should be known. If not specified one will be generated.
     * @param ?string $type
     *     The class or interface type of events for which this listener will be registered. If not provided
     *     it will be derived based on the type declaration of the listener.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $type = null,
    ) {
        if ($id || $this->type) {
            $this->hasDefinition = true;
        }
    }

    public function fromReflection(\ReflectionMethod $subject): void
    {
        $this->paramCount = $subject->getNumberOfRequiredParameters();
        if ($this->paramCount === 1) {
            $params = $subject->getParameters();
            // getName() isn't part of the interface, but is present. PHP bug.
            // @phpstan-ignore-next-line
            $this->type ??= $params[0]->getType()?->getName();
        }
    }

    /**
     * This will only get called when this attribute is on a class.
     *
     * @param Listener[] $methods
     */
    public function setMethods(array $methods): void
    {
        $this->methods = $methods;
    }

    public function includeMethodsByDefault(): bool
    {
        return true;
    }

    public function methodAttribute(): string
    {
        return __CLASS__;
    }

    /**
     * @param array<string, Listener> $methods
     */
    public function setStaticMethods(array $methods): void
    {
        $this->staticMethods = $methods;
    }

    public function includeStaticMethodsByDefault(): bool
    {
        return true;
    }

    public function staticMethodAttribute(): string
    {
        return __CLASS__;
    }


    /**
     * This will only get called when this attribute is used on a method.
     *
     * @param Listener $class
     */
    public function fromClassAttribute(object $class): void
    {
        $this->id ??= $class->id;
        $this->type ??= $class->type;
        $this->priority ??= $class->priority;
        $this->before = [...$this->before, ...$class->before];
        $this->after = [...$this->after, ...$class->after];
    }

    public function subAttributes(): array
    {
        return [
            ListenerBefore::class => 'fromBefore',
            ListenerAfter::class => 'fromAfter',
            ListenerPriority::class => 'fromPriority',
        ];
    }

    /**
     * @param array<ListenerBefore> $attribs
     */
    public function fromBefore(array $attribs): void
    {
        if ($attribs) {
            $this->hasDefinition ??= true;
        }
        foreach ($attribs as $attrib) {
            $this->id ??= $attrib->id;
            $this->type ??= $attrib->type;
            $this->before = [...$this->before, ...$attrib->before];
        }
    }

    /**
     * @param array<ListenerAfter> $attribs
     */
    public function fromAfter(array $attribs): void
    {
        if ($attribs) {
            $this->hasDefinition ??= true;
        }
        foreach ($attribs as $attrib) {
            $this->id ??= $attrib->id;
            $this->type ??= $attrib->type;
            $this->after = [...$this->after, ...$attrib->after];
        }
    }

    public function fromPriority(?ListenerPriority $attrib): void
    {
        if ($attrib) {
            $this->hasDefinition ??= true;
        }
        $this->id ??= $attrib?->id;
        $this->type ??= $attrib?->type;
        $this->priority = $attrib?->priority;
    }

    public function finalize(): void
    {
        $this->methods ??= [];
        $this->hasDefinition ??= false;
    }

}
