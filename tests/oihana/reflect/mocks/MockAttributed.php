<?php

namespace tests\oihana\reflect\mocks;

/**
 * Mock carrying attributes on its class, a property and a method, to test the readers.
 */
#[MockMarker( 'on-class' )]
class MockAttributed
{
    #[MockMarker( 'on-prop' )]
    public int $value = 0;

    #[MockMarker( 'on-method' )]
    public function run(): void
    {
    }
}
