<?php

namespace tests\oihana\reflect\mocks;

/**
 * A pure (non-backed) enum used to test that scalar hydration is rejected.
 */
enum MockColor
{
    case Red;
    case Blue;
}
