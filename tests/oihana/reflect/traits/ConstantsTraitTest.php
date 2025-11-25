<?php

namespace tests\oihana\reflect\traits;

use oihana\reflect\traits\ConstantsTrait;
use PHPUnit\Framework\TestCase;

use oihana\reflect\exceptions\ConstantException;
use ReflectionClass;

/**
 * Test the ConstantsTrait helper.
 */
final class ConstantsTraitTestClass
{
    use ConstantsTrait;

    public const string FOO   = 'foo';
    public const string BAR   = 'bar';
    public const array  BAZ   = ['baz1', 'baz2', 'baz3'];
    public const string MULTI = 'one,two,three';
}

class ConstantsTraitTest extends TestCase
{
    protected function setUp(): void
    {
        ConstantsTraitTestClass::resetCaches();
    }

    public function testGetAllReturnsConstants()
    {
        $all = ConstantsTraitTestClass::getAll();
        $this->assertIsArray( $all );
        $this->assertArrayHasKey('FOO' , $all ) ;
        $this->assertArrayHasKey('BAZ' , $all ) ;
        $this->assertIsArray( $all['BAZ'] );
        $this->assertEquals(['baz1', 'baz2', 'baz3'], $all['BAZ']);
    }

    public function testEnumsReturnsUniqueSortedValues()
    {
        // The enums method must merge the array value into simple values
        $enums = ConstantsTraitTestClass::enums();
        $expected = ['bar', 'baz1', 'baz2', 'baz3', 'foo', 'one,two,three' ] ;
        $this->assertEquals( $expected , $enums ) ;
    }

    public function testGetConstantReturnsConstantName()
    {
        // Valeur simple
        $this->assertSame('FOO', ConstantsTraitTestClass::getConstant('foo'));

        // Valeur dans un tableau
        $this->assertSame('BAZ', ConstantsTraitTestClass::getConstant('baz2'));
        $this->assertSame('BAZ', ConstantsTraitTestClass::getConstant('baz1'));

        // Valeur dans une chaîne avec séparateur ','
        $this->assertSame('MULTI', ConstantsTraitTestClass::getConstant('two', ','));
        $this->assertSame('MULTI', ConstantsTraitTestClass::getConstant('one', ','));

        // Valeur inexistante => null
        $this->assertNull(ConstantsTraitTestClass::getConstant('notfound'));

        // Test with array of separators
        // Suppose we modify MULTI to have multiple separators for test, e.g. 'one|two,three'
        // To simulate, we'll temporarily mock getAll to return a custom constant:
        $reflection = new ReflectionClass(ConstantsTraitTestClass::class);
        $property = $reflection->getProperty('ALL');
        $property->setValue
        (
            null,
            [
                'FOO' => 'foo',
                'MULTI' => 'one|two,three',
                'BAZ' => ['baz1', 'baz2', 'baz3']
            ]
        );
        ConstantsTraitTestClass::resetCaches(); // reset cache again after hack

        // Now test with multiple separators: first split by '|' then by ','
        $this->assertSame('MULTI', ConstantsTraitTestClass::getConstant('one', ['|', ',']));
        $this->assertSame('MULTI', ConstantsTraitTestClass::getConstant('two', ['|', ',']));
        $this->assertSame('MULTI', ConstantsTraitTestClass::getConstant('three', ['|', ',']));
        $this->assertNull(ConstantsTraitTestClass::getConstant('four', ['|', ',']));
    }

    public function testGetConstantCaseInsensitive()
    {
        $reflection = new ReflectionClass(ConstantsTraitTestClass::class);
        $prop = $reflection->getProperty('ALL' ) ;
        $prop->setValue(null ,
        [
            'FOO' => 'foo',
            'MULTI' => 'one|Two,three',
            'BAZ' => ['baz1','baz2','baz3']
        ]);
        ConstantsTraitTestClass::resetCaches();

        // Simple case-insensitive
        $this->assertSame('FOO', ConstantsTraitTestClass::getConstant('FOO', caseInsensitive: true));
        $this->assertSame('FOO', ConstantsTraitTestClass::getConstant('foo', caseInsensitive: true));
        $this->assertSame('FOO', ConstantsTraitTestClass::getConstant('FoO', caseInsensitive: true));

        // Array constant
        $this->assertSame('BAZ', ConstantsTraitTestClass::getConstant('BAZ1', caseInsensitive: true));
        $this->assertSame('BAZ', ConstantsTraitTestClass::getConstant('baz2', caseInsensitive: true));
        $this->assertSame('BAZ', ConstantsTraitTestClass::getConstant('BaZ3', caseInsensitive: true));

        // String with separator, multi-separator
        $this->assertSame('MULTI', ConstantsTraitTestClass::getConstant('ONE'   , ['|',','] , true));
        $this->assertSame('MULTI', ConstantsTraitTestClass::getConstant('two'   , ['|',','] , true));
        $this->assertSame('MULTI', ConstantsTraitTestClass::getConstant('THREE' , ['|',','] , true));

        // Not found
        $this->assertNull(ConstantsTraitTestClass::getConstant('FOOBAR', null, true));
    }

    public function testIncludesFindsValueInSimpleConstant()
    {
        $this->assertTrue(ConstantsTraitTestClass::includes('foo'));
        $this->assertFalse(ConstantsTraitTestClass::includes('notfound'));
    }

    public function testIncludesFindsValueInArrayConstant()
    {
        $this->assertTrue(ConstantsTraitTestClass::includes('baz2'));
        $this->assertFalse(ConstantsTraitTestClass::includes('baz4'));
    }

    public function testGetReturnsValueIfIncluded()
    {
        $this->assertSame('foo', ConstantsTraitTestClass::get('foo', 'default'));
        $this->assertSame('default', ConstantsTraitTestClass::get('notfound', 'default'));
    }

    public function testIncludesWithSeparatorWorks()
    {
        $this->assertTrue(ConstantsTraitTestClass::includes('two', false, ','));
        $this->assertFalse(ConstantsTraitTestClass::includes('four', false, ','));
    }

    /**
     * @throws ConstantException
     */
    public function testValidateDoesNotThrowForValidValues()
    {
        $this->expectNotToPerformAssertions();

        ConstantsTraitTestClass::validate('foo');
        ConstantsTraitTestClass::validate('baz3');
        ConstantsTraitTestClass::validate('two', false, ','); // Cette ligne ne correspond pas à la signature, à ignorer ou adapter
    }

    public function testValidateThrowsExceptionForInvalidValue()
    {
        $this->expectException(ConstantException::class);
        ConstantsTraitTestClass::validate('invalid');
    }

}