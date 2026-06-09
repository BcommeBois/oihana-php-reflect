<?php

namespace oihana\reflect\attributes;

use Attribute;

/**
 * Marks a public property to be ignored during (de)serialization.
 *
 * This is an **equivalent alias** of {@see Transient}: a property carrying either attribute
 * is excluded from {@see \oihana\reflect\Reflection::hydrate()} (input) and from
 * {@see \oihana\reflect\traits\ReflectionTrait::toArray()} (output). Detection relies on
 * `ReflectionAttribute::IS_INSTANCEOF`, so this subclass is recognized wherever `Transient` is.
 *
 * Use whichever name reads best in your codebase — the behaviour is identical.
 *
 * @example
 * ```php
 * use oihana\reflect\attributes\HydrateIgnore;
 *
 * class User
 * {
 *     public string $name = '';
 *
 *     #[HydrateIgnore]
 *     public ?string $cachedToken = null; // never hydrated nor serialized
 * }
 * ```
 *
 * @package oihana\reflect\attributes
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.5
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
class HydrateIgnore extends Transient
{
}
