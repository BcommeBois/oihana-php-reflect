<?php

namespace tests\oihana\reflect\helpers;

use tests\oihana\reflect\helpers\samples\SampleClass;

use PHPUnit\Framework\TestCase;

use function oihana\reflect\helpers\getFunctionInfo;

class GetFunctionInfoTest extends TestCase
{
    public function testUserFunction() :void
    {
        require_once __DIR__ . '/samples/sample_functions.php';

        $info = getFunctionInfo( 'sample_user_function' );

        $this->assertIsArray($info);
        $this->assertSame('sample_user_function', $info['name']);
        $this->assertSame('', $info['namespace']);
        $this->assertSame('sample_user_function', $info['alias']);
        $this->assertStringEndsWith('sample_functions.php', $info['file']);
        $this->assertIsInt($info['startLine']);
        $this->assertIsInt($info['endLine']);
        $this->assertFalse($info['isInternal']);
        $this->assertTrue($info['isUser']);
        $this->assertStringContainsString('@return string', $info['comment']);
    }

    public function testInternalFunction() :void
    {
        $info = getFunctionInfo( 'strlen' );

        $this->assertIsArray($info);
        $this->assertSame('strlen', $info['name']);
        $this->assertSame('', $info['namespace']);
        $this->assertSame('strlen', $info['alias']);
        $this->assertTrue($info['isInternal']);
        $this->assertFalse($info['isUser']);
        $this->assertContains(
            gettype($info['file']),
            ['string', 'NULL', 'boolean'],
            'Expected file to be string, null or false'
        );
    }

    public function testClosure() :void
    {
        $closure = function () { return 'hello'; };

        $info = getFunctionInfo( $closure );

        $this->assertIsArray($info);
        $this->assertMatchesRegularExpression('/^\{closure.*\}$/', $info['alias']);
        $this->assertFalse($info['isInternal']);
        $this->assertTrue($info['isUser']);
    }

    public function testStaticMethod() :void
    {
        require_once __DIR__ . '/samples/SampleClass.php';

        $info = getFunctionInfo( 'tests\\oihana\\reflect\\helpers\\samples\\SampleClass::staticMethod' );
        $this->assertIsArray($info);
        $this->assertSame('tests\\oihana\\reflect\\helpers\\samples\\SampleClass::staticMethod', $info['name']);
        $this->assertSame('tests\\oihana\\reflect\\helpers\\samples', $info['namespace']);
        $this->assertSame('staticMethod', $info['alias']);
    }

    public function testInstanceMethodArray() :void
    {
        require_once __DIR__ . '/samples/SampleClass.php';

        $obj = new SampleClass();

        $info = getFunctionInfo( [ $obj , 'instanceMethod' ] );

        $this->assertIsArray($info);
        $this->assertSame('tests\\oihana\\reflect\\helpers\\samples\\SampleClass::instanceMethod', $info['name']);
    }

    public function testNonExistentFunction() :void
    {
        $info = getFunctionInfo( 'this_function_does_not_exist' );
        $this->assertNull($info);
    }

    public function testInvalidCallable() :void
    {
        $info = getFunctionInfo( 42 ); // Not a valid callable
        $this->assertNull($info);
    }
}