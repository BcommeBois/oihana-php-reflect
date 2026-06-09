<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateAs;

class MockHydrateAs
{
    #[HydrateAs( MockAddress::class )]
    public mixed $payload = null ;
}
