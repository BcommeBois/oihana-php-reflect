<?php

namespace oihana\reflect\attributes;

use Attribute;

/**
 * Marks a public property as **transient**: it is excluded from both directions of
 * serialization handled by this package.
 *
 * - {@see \oihana\reflect\Reflection::hydrate()} never reads it from the input data
 *   (so incoming values cannot overwrite it).
 * - {@see \oihana\reflect\traits\ReflectionTrait::toArray()} never emits it.
 *
 * Typical use: computed / derived properties that must not be populated from data nor
 * exposed in the serialized form.
 *
 * {@see HydrateIgnore} is an equivalent alias of this attribute.
 *
 * @example
 * ```php
 * use oihana\reflect\attributes\Transient;
 *
 * class Invoice
 * {
 *     public float $subtotal = 0.0;
 *     public float $tax      = 0.0;
 *
 *     #[Transient]
 *     public float $total = 0.0; // computed; never hydrated nor serialized
 * }
 * ```
 *
 * @package oihana\reflect\attributes
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.5
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
class Transient
{
}
