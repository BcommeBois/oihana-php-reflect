<?php

namespace tests\oihana\reflect\mocks;

/**
 * An int-backed enum used to test enum hydration.
 */
enum MockPriority: int
{
    case Low  = 1;
    case High = 10;
}
