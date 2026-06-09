<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\attributes\HydrateWith;

class MockHydrateWithEmpty
{
    #[HydrateWith()]
    public array $items = [] ;
}
