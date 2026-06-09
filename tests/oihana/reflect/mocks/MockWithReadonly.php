<?php

namespace tests\oihana\reflect\mocks;

/**
 * Mock exercising readonly and asymmetric-visibility (PHP 8.4) hydration.
 */
class MockWithReadonly
{
    /**
     * Readonly property (no constructor) : must be initialized by hydration.
     */
    public readonly string $id;

    /**
     * Asymmetric visibility : public read, private write.
     */
    public private(set) int $score = 0;

    /**
     * Asymmetric visibility : public read, protected write.
     */
    public protected(set) string $tag = '';

    /**
     * Plain mutable property (non-regression + scalar coercion).
     */
    public int $count = 0;
}
