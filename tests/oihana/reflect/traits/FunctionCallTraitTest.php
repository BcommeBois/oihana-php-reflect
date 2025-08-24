<?php

namespace tests\oihana\reflect\traits;

use oihana\reflect\traits\FunctionCallTrait;
use PHPUnit\Framework\TestCase;

class FunctionCallTraitTest extends TestCase
{
    use FunctionCallTrait;

    private const string APPEND = 'APPEND';
    private const string MERGE  = 'MERGE';

    public function testGetFunctionName(): void
    {
        $expr = 'APPEND([1,2], 3)';

        $this->assertSame('APPEND', static::getFunctionName($expr));
        $this->assertSame('APPEND', static::getFunctionName($expr, 'upper'));
        $this->assertSame('append', static::getFunctionName($expr, 'lower'));

        $this->assertNull(static::getFunctionName('unknown(1)'));
    }

    public function testGetArguments(): void
    {
        $expr = 'APPEND([1,2], 3)';
        $expected = ['[1,2]', '3'];

        $this->assertSame($expected, static::getArguments($expr));
        $this->assertSame($expected, static::getArguments($expr, 'upper'));
        $this->assertSame($expected, static::getArguments($expr, 'lower'));

        $this->assertNull(static::getArguments('invalid'));
    }

    public function testIsFunctionCall(): void
    {
        $expr = 'APPEND([1,2], 3)';

        $this->assertTrue(static::isFunctionCall($expr));
        $this->assertTrue(static::isFunctionCall($expr, 'upper'));
        $this->assertTrue(static::isFunctionCall($expr, 'lower'));

        $this->assertFalse(static::isFunctionCall('foo([1,2])'));
    }

    public function testSplitExpression(): void
    {
        $expr = 'APPEND([1,2], 3)';

        $expectedDefault =
        [
            'function' => 'APPEND',
            'arguments' => ['[1,2]', '3']
        ];

        $expectedUpper =
        [
            'function' => 'append',
            'arguments' => ['[1,2]', '3']
        ];

        $this->assertSame($expectedDefault, static::splitExpression($expr));
        $this->assertSame($expectedUpper, static::splitExpression($expr, 'lower'));
        $this->assertNull(static::splitExpression('invalid'));
    }

    public function testToCanonicalExpression(): void
    {
        $expr = 'APPEND([1,2],3)';

        $this->assertSame('APPEND([1,2], 3)', static::toCanonicalExpression($expr));
        $this->assertSame('APPEND([1,2], 3)', static::toCanonicalExpression($expr, 'upper'));
        $this->assertSame('append([1,2], 3)', static::toCanonicalExpression($expr, 'lower'));
        $this->assertNull(static::toCanonicalExpression('invalid'));
    }

    public function testReplaceArguments(): void
    {
        $expr = 'APPEND([1,2], 3)';
        $newArgs = ['[4,5]', '6'];

        $this->assertSame('APPEND([4,5], 6)', static::replaceArguments($expr, $newArgs));
        $this->assertSame('APPEND([4,5], 6)', static::replaceArguments($expr, $newArgs, 'upper'));
        $this->assertNull(static::replaceArguments('invalid', $newArgs));
    }

    public function testIsValidArguments(): void
    {
        $expr = 'APPEND([1,2], 3)';

        $this->assertTrue(static::isValidArguments($expr, 2));
        $this->assertTrue(static::isValidArguments($expr, 2, 3));
        $this->assertFalse(static::isValidArguments($expr, 3));
        $this->assertFalse(static::isValidArguments('invalid', 1));
    }

    public function testFunctionCallCaseSensitive()
    {
        $this->assertTrue( self::isFunctionCall('APPEND([1,2], 3)'));

        $this->assertFalse(self::isFunctionCall('append([1,2], 3)'));

        $this->assertTrue(self::isFunctionCall('APPEND([1,2], 3)', 'upper'));

        $this->assertTrue(self::isFunctionCall('append([1,2], 3)', 'lower'));
    }
}