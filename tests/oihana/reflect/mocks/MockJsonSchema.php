<?php

namespace tests\oihana\reflect\mocks;

use DateTime;
use DateTimeImmutable;

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

    public ?MockAddress $maybeAddress = null ;  // nullable class -> oneOf keeps the $ref and its "Type: X" description

    public iterable $stream ;                   // unmapped, non-class type -> mixed

    public string $noDoc = 'plain' ;            // no docblock -> null description

    public \Stringable|int $iface ;             // union member mapping to a list of types (interface -> mixed)

    public ?string $atType = null ;             // JSON-LD metadata -> skipped

    public MockStatus $status ;                 // string-backed enum -> { type: string, enum: [...values...] }

    public ?MockPriority $priority = null ;     // int-backed nullable enum -> oneOf: [ null, { integer, enum } ]

    public MockColor $color ;                   // pure enum -> case names + $comment "not hydratable"

    public ?MockColor $optionalColor = null ;   // nullable pure enum -> oneOf wrapping the enum sub-schema

    public DateTimeImmutable $createdAt ;        // date type -> { type: string, format: date-time }

    public ?DateTime $updatedAt = null ;         // nullable date -> oneOf: [ null, { string, format: date-time } ]
}
