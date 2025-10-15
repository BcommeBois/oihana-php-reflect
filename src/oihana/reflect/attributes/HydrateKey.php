<?php

namespace oihana\reflect\attributes;

use Attribute;

/**
 * Overrides the expected array key used to hydrate a property.
 *
 * By default, `Reflection::hydrate()` maps array keys to property names. This attribute allows
 * customizing that mapping, e.g., when the source data uses different naming conventions (e.g., snake_case vs camelCase),
 * or when the key needs to be renamed for compatibility reasons.
 *
 * @example
 * Map an incoming key to a different property name
 * ```php
 * class User
 * {
 *     #[HydrateKey('user_name')]
 *     public string $name;
 * }
 *
 * $data = ['user_name' => 'Charlie'];
 * $user = (new Reflection())->hydrate($data, User::class);
 * echo $user->name; // "Charlie"
 * ```
 *
 * With optional nullable properties
 * ```php
 * class Product
 * {
 *     #[HydrateKey('product_id')]
 *     public ?int $id;
 * }
 * ```
 *
 * @package oihana\reflect\attributes
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HydrateKey
{
    public function __construct
    (
        public string $key
    )
    {}
}