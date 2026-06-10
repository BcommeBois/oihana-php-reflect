<?php

namespace tests\oihana\reflect\mocks;

use Attribute;

/**
 * A multi-target attribute used to test the attribute readers.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD )]
class MockMarker
{
    public function __construct( public string $tag = '' )
    {
    }
}
