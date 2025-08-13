<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateKey;

class MockWithRenameKey
{
    #[HydrateKey('user_name')]
    public ?string $name = null ;
}