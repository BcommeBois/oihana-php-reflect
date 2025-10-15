<?php

namespace tests\oihana\reflect\traits;

use Closure;
use oihana\reflect\traits\JsonSchemaTrait;
use PHPUnit\Framework\TestCase;

use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use function oihana\core\strings\toPhpString;

final class JsonSchemaTraitTest extends TestCase
{
    public function createTestClass(): object
    {
        return new class {
            use JsonSchemaTrait;

            /**
             * The name
             * @var string
             */
            public string $name = 'test';

            /**
             * Optional value
             * @var int|null
             */
            public ?int $age = null;

            /** @var array */
            public array $tags = ['php', 'unit'];

            /** @var mixed */
            public mixed $misc;
        };
    }

    public function testJsonSchemaStatic(): void
    {
        $obj = $this->createTestClass();
        $schema = $obj::jsonSchema();

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('age', $schema['properties']);
    }

    public function testToJsonSchemaInstance(): void
    {
        $obj = $this->createTestClass();
        $schema = $obj->toJsonSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('tags', $schema['properties']);
    }

    public function testValidateWithJsonSchemaValid(): void
    {
        $obj = $this->createTestClass();
        $data = [
            'name' => 'Alice',
            'age'  => 30,
            'tags' => ['php'],
            'misc' => 'anything'
        ];

        $result = $obj::validateWithJsonSchema($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateWithJsonSchemaInvalid(): void
    {
        $obj = $this->createTestClass();
        $data = [
            'name'    => 123 , // should be string
            'age'     => '30' , // should be int|null
            'unknown' => 'oops'
        ];

        $result = $obj::validateWithJsonSchema( $data );

        $this->assertFalse    ( $result[ 'valid'  ] );
        $this->assertNotEmpty ( $result[ 'errors' ] );

        $expected = [
            "Property 'name' should be of type [string], got integer",
            "Property 'age' should be of type [null,integer], got string",
            "Property 'unknown' is not defined in schema"
        ];

        foreach ($expected as $msg)
        {
            $this->assertTrue
            (
                in_array($msg, $result['errors'] ) ,
                "Expected error '$msg' not found in actual errors"
            );
        }
    }

    /**
     * @throws ReflectionException
     */
    public function testExtractShortDescription(): void
    {
        $obj = $this->createTestClass();
        $reflectionProp = new ReflectionProperty($obj, 'name');

        $desc = $this->getObjectMethod($obj, 'extractShortDescription')($reflectionProp);

        $this->assertEquals('The name', $desc);
    }

    /**
     * @throws ReflectionException
     */
    public function testMapPhpTypeToJsonSchema(): void
    {
        $obj = $this->createTestClass();
        $reflectionProp = new ReflectionProperty($obj, 'age');
        $type = $reflectionProp->getType();

        $mapped = $this->getObjectMethod($obj, 'mapPhpTypeToJsonSchema')($type);

        $this->assertArrayHasKey('type', $mapped);
        $this->assertEquals('integer', $mapped['type']);
    }

    /**
     * @throws ReflectionException
     */
    private function getObjectMethod( $object, string $method) : Closure
    {
        $refMethod = new ReflectionMethod($object, $method);
        return fn(...$args) => $refMethod->invokeArgs($object, $args);
    }

}