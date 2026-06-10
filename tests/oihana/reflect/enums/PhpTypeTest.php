<?php

namespace tests\oihana\reflect\enums;

use oihana\reflect\enums\PhpType;

use PHPUnit\Framework\TestCase;

final class PhpTypeTest extends TestCase
{
    public function testIsScalarTrue(): void
    {
        $this->assertTrue( PhpType::isScalar( PhpType::STRING ) );
        $this->assertTrue( PhpType::isScalar( PhpType::INTEGER ) );
        $this->assertTrue( PhpType::isScalar( PhpType::FLOAT ) );
        $this->assertTrue( PhpType::isScalar( PhpType::BOOLEAN ) );
        $this->assertTrue( PhpType::isScalar( PhpType::NUMBER ) ); // alias of float
    }

    public function testIsScalarFalse(): void
    {
        $this->assertFalse( PhpType::isScalar( PhpType::ARRAY ) );
        $this->assertFalse( PhpType::isScalar( PhpType::OBJECT ) );
        $this->assertFalse( PhpType::isScalar( PhpType::MIXED ) );
        $this->assertFalse( PhpType::isScalar( PhpType::NULL ) );
        $this->assertFalse( PhpType::isScalar( 'DateTimeImmutable' ) );
    }

    public function testIsNumericTrue(): void
    {
        $this->assertTrue( PhpType::isNumeric( PhpType::INTEGER ) );
        $this->assertTrue( PhpType::isNumeric( PhpType::FLOAT ) );
    }

    public function testIsNumericFalse(): void
    {
        $this->assertFalse( PhpType::isNumeric( PhpType::STRING ) );
        $this->assertFalse( PhpType::isNumeric( PhpType::BOOLEAN ) );
        $this->assertFalse( PhpType::isNumeric( PhpType::ARRAY ) );
    }
}
