<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateWith;

/**
 * Mock exercising every enum hydration path.
 */
class MockWithEnum
{
    /**
     * Required string-backed enum.
     */
    public MockStatus $status;

    /**
     * Nullable int-backed enum.
     */
    public ?MockPriority $priority = null;

    /**
     * Array of enums declared via #[HydrateWith].
     *
     * @var MockStatus[]
     */
    #[HydrateWith( MockStatus::class )]
    public array $history = [];

    /**
     * Array of enums declared via PHPDoc only.
     *
     * @var \tests\oihana\reflect\mocks\MockPriority[]
     */
    public array $levels = [];
}
