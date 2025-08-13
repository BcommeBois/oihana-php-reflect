<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateWith;

class MockGeo
{
    #[HydrateWith( MockAddress::class ) ]
    public array $locations = [];
}
