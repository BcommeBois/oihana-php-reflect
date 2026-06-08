<?php

namespace tests\oihana\reflect\attributes;

use oihana\reflect\attributes\HydrateAs;
use PHPUnit\Framework\TestCase;

final class HydrateAsTest extends TestCase
{
    public function testConstructorStoresTargetClass(): void
    {
        $attribute = new HydrateAs('Some\\Target\\Class');

        $this->assertSame('Some\\Target\\Class', $attribute->class);
    }
}
