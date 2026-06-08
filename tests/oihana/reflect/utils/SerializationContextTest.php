<?php

namespace tests\oihana\reflect\utils;

use oihana\reflect\utils\SerializationContext;
use PHPUnit\Framework\TestCase;

final class SerializationContextTest extends TestCase
{
    protected function tearDown(): void
    {
        // The context is global static state — always restore it empty so a
        // test never leaks options into the next one.
        SerializationContext::reset();
    }

    public function testSetOptionsThenGetOptions(): void
    {
        SerializationContext::setOptions([ 'pretty' => true ]);

        $this->assertSame([ 'pretty' => true ], SerializationContext::getOptions());
    }

    public function testResetRestoresPreviousOptions(): void
    {
        SerializationContext::setOptions([ 'a' => 1 ]);

        SerializationContext::reset([ 'b' => 2 ]);
        $this->assertSame([ 'b' => 2 ], SerializationContext::getOptions());

        SerializationContext::reset();
        $this->assertSame([], SerializationContext::getOptions());
    }
}
