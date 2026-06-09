<?php

namespace tests\oihana\reflect\mocks;

/**
 * Mock exercising pure (non-backed) enum hydration paths.
 */
class MockWithPureEnum
{
    public MockColor $color;

    public ?MockColor $optional = null;
}
