<?php

namespace tests\oihana\reflect\mocks;

/**
 * A string-backed enum used to test enum hydration.
 */
enum MockStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
