<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateIgnore;
use oihana\reflect\attributes\Transient;
use oihana\reflect\traits\ReflectionTrait;

/**
 * Mock exercising #[Transient] / #[HydrateIgnore] on both hydration and toArray().
 */
class MockTransient
{
    use ReflectionTrait;

    public float $subtotal = 0.0;

    public float $tax = 0.0;

    #[Transient]
    public float $total = 0.0;

    #[HydrateIgnore]
    public ?string $cachedToken = null;
}
