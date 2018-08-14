<?php
declare(strict_types=1);

namespace Crell\Tukio\Annotations;


use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 *
 */
class Listener
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $before;

    /** @var string */
    protected $after;

    /** @var int */
    protected $priority;


    public function __construct(array $values)
    {
        if (empty($values['id']) && empty($values['before']) && empty($values['after']) && empty($values['priority'])) {
            throw new \InvalidArgumentException("You must specify at least one property for a listener annotation.");
        }

        if (array_key_exists('before', $values) && array_key_exists('after', $values)) {
            throw new \InvalidArgumentException("You may only specify one of 'before' or 'after' in listener annotations.");
        }
        if (array_key_exists('priority', $values) && (array_key_exists('before', $values) || array_key_exists('after', $values))) {
            throw new \InvalidArgumentException("You may not specify both a listener priority and a before/after directive.");
        }

        foreach (['id', 'before', 'after', 'priority'] as $key) {
            if (!empty($values[$key])) {
                $this->$key = $values[$key];
            }
        }
    }
}
