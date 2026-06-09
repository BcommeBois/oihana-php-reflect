<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateWith;

class MockHydrateWithBogus
{
    #[HydrateWith( 'Bogus\\Does\\NotExist' )]
    public array $items = [] ;
}
