<?php

namespace tests\oihana\reflect\mocks;

use DateTimeImmutable;

use oihana\reflect\attributes\HydrateKey;
use oihana\reflect\traits\ReflectionTrait;

/**
 * Mock exercising toArray() symmetry: #[HydrateKey] reverse mapping and date → ISO.
 */
class MockSerialize
{
    use ReflectionTrait;

    #[HydrateKey( 'user_name' )]
    public string $name = 'Bob';

    public int $age = 30;

    public ?DateTimeImmutable $createdAt = null;
}
