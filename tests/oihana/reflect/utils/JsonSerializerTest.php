<?php

namespace tests\oihana\reflect\utils ;

use oihana\reflect\utils\JsonSerializer;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

/**
 * Unit tests for JsonSerializer class
 *
 * Run with: vendor/bin/phpunit tests/JsonSerializerTest.php
 */
#[CoversClass(JsonSerializer::class)]
class JsonSerializerTest extends TestCase
{
    #[Test]
    public function encodeSimpleArray(): void
    {
        $data = ['name' => 'Alice', 'age' => 30];
        $result = JsonSerializer::encode($data);
        $this->assertJsonStringEqualsJsonString(
            '{"name":"Alice","age":30}',
            $result
        );
    }

    #[Test]
    public function encodeSimpleObject(): void
    {
        $obj = new stdClass();
        $obj->name = 'Bob';
        $obj->age = 25;

        $result = JsonSerializer::encode($obj);

        $this->assertJsonStringEqualsJsonString(
            '{"name":"Bob","age":25}',
            $result
        );
    }

    #[Test]
    public function encodeWithJsonPrettyPrintFlag(): void
    {
        $data = ['name' => 'Charlie', 'age' => 35];
        $result = JsonSerializer::encode($data, JSON_PRETTY_PRINT);

        $this->assertStringContainsString("\n", $result);
        $this->assertStringContainsString('    ', $result); // Indentation
    }

    #[Test]
    public function encodeWithJsonUnescapedSlashesFlag(): void
    {
        $data = ['url' => 'https://example.com/path'];
        $result = JsonSerializer::encode($data, JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('https://example.com/path', $result);
        $this->assertStringNotContainsString('\/', $result);
    }

    #[Test]
    public function temporaryOptionsAreResetAfterEncode(): void
    {
        $initialOptions = JsonSerializer::getOptions();
        $this->assertEmpty($initialOptions);

        $data = ['test' => 'data'];
        $tempOptions = ['option1' => 'value1', 'option2' => 'value2'];

        JsonSerializer::encode($data, options: $tempOptions);

        $finalOptions = JsonSerializer::getOptions();
        $this->assertEquals($initialOptions, $finalOptions);
        $this->assertEmpty($finalOptions);
    }

    #[Test]
    public function temporaryOptionsAreResetOnJsonEncodeError(): void
    {
        $initialOptions = JsonSerializer::getOptions();

        // Create a resource that cannot be JSON encoded
        $resource = fopen('php://memory', 'r');

        try {
            JsonSerializer::encode(['resource' => $resource], options: ['test' => 'option']);
        } catch (\Exception $e) {
            // Exception may or may not be thrown depending on PHP version
        } finally {
            fclose($resource);
        }

        $finalOptions = JsonSerializer::getOptions();
        $this->assertEquals($initialOptions, $finalOptions);
    }

    #[Test]
    public function getOptionsReturnsEmptyArrayByDefault(): void
    {
        $options = JsonSerializer::getOptions();

        $this->assertIsArray($options);
        $this->assertEmpty($options);
    }

    #[Test]
    public function encodeWithMultipleOptions(): void
    {
        $options = [
            'option1' => 'value1',
            'option2' => 'value2',
            'option3' => ['nested' => 'value']
        ];

        $data = ['test' => 'data'];
        $result = JsonSerializer::encode( $data , options: $options);

        $this->assertJsonStringEqualsJsonString('{"test":"data"}', $result);
    }

    #[Test]
    public function encodeEmptyArray(): void
    {
        $result = JsonSerializer::encode([]);

        $this->assertEquals('[]', $result);
    }

    #[Test]
    public function encodeNull(): void
    {
        $result = JsonSerializer::encode(null);

        $this->assertEquals('null', $result);
    }

    #[Test]
    public function encodeNestedArrays(): void
    {
        $data = [
            'person' => [
                'name' => 'Alice',
                'address' => [
                    'city' => 'Paris',
                    'country' => 'France'
                ]
            ]
        ];

        $result = JsonSerializer::encode($data);

        $this->assertJsonStringEqualsJsonString(
            '{"person":{"name":"Alice","address":{"city":"Paris","country":"France"}}}',
            $result
        );
    }

    #[Test]
    public function encodeArrayOfObjects(): void
    {
        $obj1 = new stdClass();
        $obj1->id = 1;
        $obj1->name = 'First';

        $obj2 = new stdClass();
        $obj2->id = 2;
        $obj2->name = 'Second';

        $result = JsonSerializer::encode([$obj1, $obj2]);

        $this->assertJsonStringEqualsJsonString(
            '[{"id":1,"name":"First"},{"id":2,"name":"Second"}]',
            $result
        );
    }

    #[Test]
    public function previousOptionsAreRestoredAfterNestedCalls(): void
    {
        // This simulates nested encode calls if they were to happen
        $outerOptions = ['outer' => 'value'];

        // First encode with outer options
        JsonSerializer::encode(['data' => 'test'], options: $outerOptions);

        // Options should be back to empty
        $this->assertEmpty(JsonSerializer::getOptions());

        // Second encode with different options
        $innerOptions = ['inner' => 'value'];
        JsonSerializer::encode(['data' => 'test2'], options: $innerOptions);

        // Options should still be empty
        $this->assertEmpty(JsonSerializer::getOptions());
    }

    #[Test]
    public function encodeWithCombinedJsonFlags(): void
    {
        $data = ['url' => 'https://example.com', 'name' => 'Test'];
        $result = JsonSerializer::encode
        (
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        $this->assertStringContainsString("\n", $result);
        $this->assertStringContainsString('https://example.com', $result);
    }

}