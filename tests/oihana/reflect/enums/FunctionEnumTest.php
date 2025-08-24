<?php

namespace tests\oihana\reflect\enums ;

use oihana\reflect\enums\FunctionEnum;
use oihana\reflect\exceptions\ConstantException;
use PHPUnit\Framework\TestCase;

final class FunctionEnumTest extends TestCase
{
    public function testConstantsExist(): void
    {
        $this->assertTrue(defined(FunctionEnum::class . '::ARGUMENTS'));
        $this->assertTrue(defined(FunctionEnum::class . '::FUNCTION'));
    }

    public function testGetAllReturnsConstants(): void
    {
        $all = FunctionEnum::getAll();

        $this->assertArrayHasKey('ARGUMENTS', $all);
        $this->assertArrayHasKey('FUNCTION', $all);

        $this->assertSame('arguments', $all['ARGUMENTS']);
        $this->assertSame('function', $all['FUNCTION']);
    }

    public function testEnumsReturnsValuesSorted(): void
    {
        $enums = FunctionEnum::enums();

        $this->assertContains('arguments', $enums);
        $this->assertContains('function', $enums);

        $this->assertCount(2, $enums);
        $this->assertEquals(['arguments', 'function'], $enums); // SORT_STRING default
    }

    public function testGetConstantReturnsName(): void
    {
        $this->assertSame('ARGUMENTS', FunctionEnum::getConstant('arguments'));
        $this->assertSame('FUNCTION', FunctionEnum::getConstant('function'));
        $this->assertNull(FunctionEnum::getConstant('unknown'));
    }

    public function testIncludes(): void
    {
        $this->assertTrue(FunctionEnum::includes('arguments'));
        $this->assertTrue(FunctionEnum::includes('function'));
        $this->assertFalse(FunctionEnum::includes('unknown'));
    }

    public function testValidate(): void
    {
        $this->expectNotToPerformAssertions();
        FunctionEnum::validate('arguments');
        FunctionEnum::validate('function');
    }

    public function testValidateThrowsException(): void
    {
        $this->expectException( ConstantException::class);
        FunctionEnum::validate('unknown');
    }
}