<?php

namespace tests\oihana\reflect\traits;

use Closure;

use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

use PHPUnit\Framework\TestCase;

use oihana\reflect\traits\JsonSchemaTrait;

use tests\oihana\reflect\mocks\MockJsonSchema;

final class JsonSchemaTraitTest extends TestCase
{
    public function createTestClass(): object
    {
        return new class
        {
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
        $obj    = $this->createTestClass();
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

        $desc = $this->getObjectMethod( $obj , 'extractShortDescription' )( $reflectionProp ) ;

        $this->assertEquals('The name' , $desc ) ;
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

    public function testJsonSchemaCoversAllPropertyShapes(): void
    {
        $schema = MockJsonSchema::jsonSchema( true ); // strict
        $props  = $schema['properties'];

        // @see https URL in the class docblock becomes $id (strict mode).
        $this->assertEquals('https://example.com/mock-schema', $schema['$id']);

        // JSON-LD metadata property is skipped.
        $this->assertArrayNotHasKey('atType', $props);

        // No type hint -> oneOf of all primitive types.
        $this->assertArrayHasKey('oneOf', $props['untyped']);

        // Union without null -> oneOf of its members.
        $this->assertArrayHasKey('oneOf', $props['code']);

        // Union with null.
        $this->assertArrayHasKey('oneOf', $props['codeOrNull']);

        // Union of two classes collapses to a single object type.
        $this->assertEquals('object', $props['entity']['type']);

        // Class-typed property -> $ref to its definition.
        $this->assertEquals('object', $props['address']['type']);
        $this->assertEquals('#/definitions/MockAddress', $props['address']['$ref']);

        // Nullable class -> oneOf keeps the $ref (and lifts the "Type: X" description to the property).
        $this->assertSame(['type' => 'null'], $props['maybeAddress']['oneOf'][0]);
        $this->assertEquals('#/definitions/MockAddress', $props['maybeAddress']['oneOf'][1]['$ref']);
        $this->assertEquals('Type: MockAddress', $props['maybeAddress']['description']);

        // Unmapped, non-class type (iterable) -> mixed (array of types).
        $this->assertIsArray($props['stream']['type']);

        // Property without a docblock has no description.
        $this->assertArrayNotHasKey('description', $props['noDoc']);
    }

    public function testBackedEnumProducesScalarTypeAndEnumValues(): void
    {
        $props = MockJsonSchema::jsonSchema( false )['properties'];

        // String-backed enum (non-null) -> { type: string, enum: [...values...] }.
        $this->assertSame('string', $props['status']['type']);
        $this->assertSame(['active', 'inactive'], $props['status']['enum']);
        $this->assertArrayNotHasKey('$ref', $props['status']);

        // Int-backed nullable enum -> oneOf: [ {null}, { type: integer, enum: [...] } ].
        $this->assertArrayHasKey('oneOf', $props['priority']);
        $this->assertSame(['type' => 'null'], $props['priority']['oneOf'][0]);
        $this->assertSame('integer', $props['priority']['oneOf'][1]['type']);
        $this->assertSame([1, 10], $props['priority']['oneOf'][1]['enum']);
    }

    public function testPureEnumListsCaseNamesAndFlagsItNotHydratable(): void
    {
        $props = MockJsonSchema::jsonSchema( false )['properties'];

        // Pure (non-backed) enum (non-null) -> case names + a $comment warning.
        $this->assertSame('string', $props['color']['type']);
        $this->assertSame(['Red', 'Blue'], $props['color']['enum']);
        $this->assertStringContainsString('not hydratable', $props['color']['$comment']);

        // Nullable pure enum -> the full enum sub-schema (type + enum + $comment) is kept inside oneOf.
        $this->assertSame(['type' => 'null'], $props['optionalColor']['oneOf'][0]);
        $nonNull = $props['optionalColor']['oneOf'][1];
        $this->assertSame('string', $nonNull['type']);
        $this->assertSame(['Red', 'Blue'], $nonNull['enum']);
        $this->assertArrayHasKey('$comment', $nonNull);
    }

    public function testDateTimePropertyMapsToStringWithDateTimeFormat(): void
    {
        $props = MockJsonSchema::jsonSchema( false )['properties'];

        // Any DateTimeInterface (non-null) -> { type: string, format: date-time }.
        $this->assertSame('string', $props['createdAt']['type']);
        $this->assertSame('date-time', $props['createdAt']['format']);
        $this->assertArrayNotHasKey('$ref', $props['createdAt']);

        // Nullable date -> oneOf: [ null, { type: string, format: date-time } ].
        $this->assertSame(['type' => 'null'], $props['updatedAt']['oneOf'][0]);
        $this->assertSame('string', $props['updatedAt']['oneOf'][1]['type']);
        $this->assertSame('date-time', $props['updatedAt']['oneOf'][1]['format']);
    }

    public function testValidateDataWithJsonSchemaInstance(): void
    {
        $obj    = new MockJsonSchema();
        $result = $obj->validateDataWithJsonSchema([ 'name' => 'ok' ]);

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testValidateAgainstSchemaWithoutPropertiesIsValid(): void
    {
        $obj = $this->createTestClass();

        $result = $this->getObjectMethod($obj, 'validateAgainstSchema')(
            [ 'anything' => 1 ],
            [ 'type' => 'object' ] // no 'properties' key
        );

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * @throws ReflectionException
     */
    public function testValidateValueWithoutTypeConstraintIsValid(): void
    {
        $obj = $this->createTestClass();

        // Schema with neither oneOf nor type -> no constraint -> no errors.
        $errors = $this->getObjectMethod($obj, 'validateValue')('whatever', [], 'path');

        $this->assertSame([], $errors);
    }

    /**
     * @throws ReflectionException
     */
    public function testValidateValueWithUnknownTypeReportsError(): void
    {
        $obj = $this->createTestClass();

        // An unknown required type falls into the match() default (no match).
        $errors = $this->getObjectMethod($obj, 'validateValue')('x', [ 'type' => 'weird' ], 'path');

        $this->assertNotEmpty($errors);
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