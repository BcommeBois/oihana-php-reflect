<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateKey;

/**
 * Mock exercising #[HydrateKey] with multiple (fallback) source keys.
 */
class MockMultiKey
{
    #[HydrateKey( 'user_name' , 'username' , 'login' )]
    public string $name = 'default';
}
