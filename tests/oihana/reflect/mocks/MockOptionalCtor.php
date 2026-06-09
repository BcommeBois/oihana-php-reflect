<?php

namespace tests\oihana\reflect\mocks;

/**
 * Class with a no-argument-callable constructor that has a side effect.
 * Hydration must keep invoking the constructor (no regression).
 */
class MockOptionalCtor
{
    public string $name = '';

    /**
     * Set by the constructor body to prove it still runs.
     */
    public bool $constructed = false;

    public function __construct( string $name = 'fallback' )
    {
        $this->name        = $name;
        $this->constructed = true;
    }
}
