<?php

namespace tests\oihana\reflect\mocks;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

use oihana\reflect\attributes\HydrateAs;

/**
 * Mock exercising every DateTime hydration path (and the "scalar wins" guard).
 */
class MockWithDate
{
    /**
     * Strict immutable date : a scalar is always converted.
     */
    public ?DateTimeImmutable $createdAt = null;

    /**
     * Strict mutable date : keeps the concrete DateTime class.
     */
    public ?DateTime $updatedAt = null;

    /**
     * Abstract interface : defaults to DateTimeImmutable.
     */
    public ?DateTimeInterface $publishedAt = null;

    /**
     * Union with a scalar : the raw string/int must be kept as-is (schema.org style).
     */
    public null|string|DateTimeInterface $endDate = null;

    /**
     * Real schema.org pattern : no date type at all, must never be touched.
     */
    public null|string|int $startDate = null;

    /**
     * Explicit override : force date parsing even though the union accepts a string.
     */
    #[HydrateAs( DateTimeImmutable::class )]
    public null|string|DateTimeInterface $forcedDate = null;

    /**
     * Array of dates declared via PHPDoc.
     *
     * @var \DateTimeImmutable[]
     */
    public array $milestones = [];
}
