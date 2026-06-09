<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateWith;

class MockHydrateWithRenameKey
{
    #[HydrateWith( MockWithRenameKey::class )]
    public array $items = [] ;
}
