<?php

namespace tests\oihana\reflect\mocks;

use DateTime;
use DateTimeImmutable;

use oihana\reflect\attributes\HydrateWith;
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

    // ---- Typed arrays -> items (S4) -----------------------------------------------------------

    /**
     * Array of enums via #[HydrateWith] -> items: backed-enum schema.
     */
    #[HydrateWith( MockStatus::class )]
    public array $history = [] ;

    /**
     * Polymorphic array via #[HydrateWith(A, B)] -> items: oneOf of $refs.
     */
    #[HydrateWith( MockAddress::class , MockUser::class )]
    public array $mixedEntities = [] ;

    /**
     * Array of int-backed enums via PHPDoc -> items: { integer, enum }.
     *
     * @var \tests\oihana\reflect\mocks\MockPriority[]
     */
    public array $levels = [] ;

    /**
     * Array of dates via PHPDoc (leading backslash) -> items: { string, format: date-time }.
     *
     * @var \DateTimeImmutable[]
     */
    public array $milestones = [] ;

    /**
     * Array of objects via PHPDoc `Type[]` -> items: { object, $ref }.
     *
     * @var \tests\oihana\reflect\mocks\MockAddress[]
     */
    public array $contacts = [] ;

    /**
     * Array of objects via PHPDoc `array<Type>` -> items: { object, $ref }.
     *
     * @var array<\tests\oihana\reflect\mocks\MockUser>
     */
    public array $members = [] ;

    /**
     * Array of scalars via PHPDoc -> no items (hydrate() leaves it untouched).
     *
     * @var string[]
     */
    public array $names = [] ;

    /**
     * #[HydrateWith] with no resolvable class -> no items.
     */
    #[HydrateWith( 'Bogus\\Does\\NotExist' )]
    public array $bogusItems = [] ;

    public array $tags = [] ;                     // untyped array -> { type: array } with no items
}
