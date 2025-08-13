<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateWith;

class MockPolymorphicContainer
{
    #[HydrateWith( MockAddress::class, MockUser::class ) ]
    public array $items = [];
}


