<?php

namespace tests\oihana\reflect\mocks;

/**
 * Class with a required constructor argument (promoted property).
 * Hydration must instantiate it without invoking the constructor.
 */
class MockRequiredCtor
{
    public int $amount = 0;

    public function __construct( public string $currency )
    {
    }
}
