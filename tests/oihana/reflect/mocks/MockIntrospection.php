<?php

namespace tests\oihana\reflect\mocks;

use ArrayAccess;
use Countable;

/**
 * Mock exercising the introspection helpers (hasMethod / hasProperty / propertyType / namespace).
 */
class MockIntrospection
{
    public int $age = 0;

    public int|string $id = 0;

    public Countable&ArrayAccess $collection;

    public $untyped;

    public function doThing(): void
    {
    }
}
