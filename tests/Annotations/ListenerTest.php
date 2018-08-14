<?php
declare(strict_types=1);

namespace Crell\Tukio\Annotations\Listener;

use Crell\Tukio\Annotations\Listener;
use PHPUnit\Framework\TestCase;

class ListenerTest extends TestCase
{

    public function test_empty_annotation_throws_exception() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("You must specify at least one property for a listener annotation.");

        $l = new Listener([]);
    }

    public function test_set_just_id_passes() : void
    {
        // The only assertion is that no exceptions were thrown.
        $this->expectNotToPerformAssertions();

        $l = new Listener(['id' => 'foo']);
    }

    public function test_setting_before_and_after_throws_exception() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("You may only specify one of 'before' or 'after' in listener annotations.");

        $l = new Listener(['before' => 'foo', 'after' => 'bar']);
    }

    public function test_can_set_before() : void
    {
        // The only assertion is that no exceptions were thrown.
        $this->expectNotToPerformAssertions();

        $l = new Listener(['id' => 'foo', 'before' > 'bar']);
    }

    public function test_can_set_after() : void
    {
        // The only assertion is that no exceptions were thrown.
        $this->expectNotToPerformAssertions();

        $l = new Listener(['id' => 'foo', 'after' > 'bar']);
    }

}
