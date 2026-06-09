<?php

namespace tests\oihana\reflect\mocks;

/**
 * Mock locking in the scalar type-coercion contract of hydration
 * (PHP coercive typing, applied through ReflectionProperty::setValue()).
 */
class MockScalarCoercion
{
    public int $count = 0;

    public float $ratio = 0.0;

    public bool $enabled = false;

    public string $label = '';
}
