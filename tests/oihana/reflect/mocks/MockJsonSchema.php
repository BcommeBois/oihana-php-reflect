<?php

namespace tests\oihana\reflect\mocks;

use oihana\reflect\traits\JsonSchemaTrait;

/**
 * Rich fixture exercising every JsonSchemaTrait property shape.
 *
 * @see https://example.com/mock-schema
 */
class MockJsonSchema
{
    use JsonSchemaTrait;

    /**
     * The display name.
     */
    public string $name = 'x';

    public $untyped ;                            // no type hint -> oneOf of all types

    public int|string $code = 1 ;               // union without null

    public int|string|null $codeOrNull = null ; // union with null

    public MockAddress|MockUser $entity ;       // union of classes -> dedup to a single object type

    public MockAddress $address ;               // class type -> $ref

    public iterable $stream ;                   // unmapped, non-class type -> mixed

    public string $noDoc = 'plain' ;            // no docblock -> null description

    public \Stringable|int $iface ;             // union member mapping to a list of types (interface -> mixed)

    public ?string $atType = null ;             // JSON-LD metadata -> skipped
}
