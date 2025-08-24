<?php

namespace tests\oihana\reflect\enums ;

use oihana\reflect\enums\CaseEnum;
use oihana\reflect\exceptions\ConstantException;
use PHPUnit\Framework\TestCase;

final class CaseEnumTest extends TestCase
{
    public function testConstantsExist(): void
    {
        $this->assertTrue(defined(CaseEnum::class . '::LOWER'));
        $this->assertTrue(defined(CaseEnum::class . '::UPPER'));
    }

    public function testGetAllReturnsConstants(): void
    {
        $all = CaseEnum::getAll();

        $this->assertArrayHasKey('LOWER', $all);
        $this->assertArrayHasKey('UPPER', $all);

        $this->assertSame('lower', $all['LOWER']);
        $this->assertSame('upper', $all['UPPER']);
    }

    public function testEnumsReturnsValuesSorted(): void
    {
        $enums = CaseEnum::enums();

        $this->assertContains('lower', $enums);
        $this->assertContains('upper', $enums);

        $this->assertCount(2, $enums);
        $this->assertEquals(['lower', 'upper'], $enums); // SORT_STRING default
    }

    public function testGetConstantReturnsName(): void
    {
        $this->assertSame('LOWER', CaseEnum::getConstant('lower'));
        $this->assertSame('UPPER', CaseEnum::getConstant('upper'));
        $this->assertNull(CaseEnum::getConstant('mixed'));
    }

    public function testIncludes(): void
    {
        $this->assertTrue(CaseEnum::includes('lower'));
        $this->assertTrue(CaseEnum::includes('upper'));
        $this->assertFalse(CaseEnum::includes('mixed'));
    }

    /**
     * @throws ConstantException
     */
    public function testValidate(): void
    {
        $this->expectNotToPerformAssertions();
        CaseEnum::validate('lower');
        CaseEnum::validate('upper');
    }

    public function testValidateThrowsException(): void
    {
        $this->expectException( ConstantException::class);
        CaseEnum::validate('mixed');
    }
}