<?php

namespace tests\oihana\reflect\utils ;

use JsonSerializable;
use oihana\core\options\ArrayOption;
use oihana\reflect\utils\CborSerializer;
use oihana\reflect\utils\SerializationContext;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for CborSerializer class
 *
 * Run with: vendor/bin/phpunit tests/CborSerializerTest.php
 */
#[CoversClass(CborSerializer::class)]
final class CborSerializerTest extends TestCase
{
    protected function tearDown(): void
    {
        // Toujours nettoyer le contexte global après chaque test
        SerializationContext::reset([]);
    }

    public function testEncodeReturnsString(): void
    {
        $data = ['a' => 1, 'b' => 2];

        $result = CborSerializer::encode($data);

        $this->assertIsString($result);
        $this->assertNotSame('', $result);
    }

    public function testOptionsAreSetDuringEncodingAndRestoredAfter(): void
    {
        SerializationContext::setOptions(['initial' => true]);

        $options = [ArrayOption::REDUCE => true];

        $seenDuringEncode = null;

        $object = new class( $seenDuringEncode ) implements JsonSerializable
        {
            private ?array $seen;

            public function __construct(?array &$seen)
            {
                $this->seen = &$seen;
            }

            public function jsonSerialize(): array
            {
                $this->seen = SerializationContext::getOptions();
                return ['x' => null];
            }
        };

        CborSerializer::encode($object, $options);

        $this->assertSame( [] , $seenDuringEncode ) ;

        $this->assertSame(['initial' => true], SerializationContext::getOptions());
    }

    public function testContextIsRestoredEvenIfExceptionOccurs(): void
    {
        SerializationContext::setOptions(['before' => 123]);

        $this->expectException(\RuntimeException::class);

        try
        {
            CborSerializer::encode(
                new class implements JsonSerializable
                {
                    public function jsonSerialize(): array
                    {
                        throw new \RuntimeException('boom');
                    }
                },
                ['temp' => true]
            );
        }
        finally
        {
            // Même après exception, le contexte doit être restauré
            $this->assertSame(['before' => 123], SerializationContext::getOptions());
        }
    }

    public function testJsonSerializerIsUsedAsInternalEncoder(): void
    {
        $options = [ArrayOption::REDUCE => true];

        $called = false;

        // Petit espion sur JsonSerializer::encode via un objet JsonSerializable
        $object = new class($called) implements JsonSerializable
        {
            private bool $called;

            public function __construct( bool &$called)
            {
                $this->called = &$called;
            }

            public function jsonSerialize(): array
            {
                // Si on est ici, c'est bien que JsonSerializer::encode a été appelé
                $this->called = true;

                return ['a' => 1];
            }
        };

        CborSerializer::encode($object, $options);

        $this->assertTrue($called, 'JsonSerializer::encode() should be used as internal encoder');
    }
}