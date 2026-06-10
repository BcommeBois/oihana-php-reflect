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
 * $user = new Reflection()->hydrate($data, User::class);
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
    /**
     * The primary source key (the first one). Kept for backward compatibility.
     */
    public string $key ;

    /**
     * All accepted source keys, in priority order. During hydration, the first key
     * present in the data wins.
     *
     * @var string[]
     */
    public array $keys ;

    /**
     * @param string $key  The primary source key.
     * @param string ...$keys Additional fallback keys, tried in order when the primary is absent.
     *
     * @example
     * ```php
     * #[HydrateKey( 'user_name' )]                 // single key
     * #[HydrateKey( 'user_name' , 'username' )]    // 'user_name' first, else 'username'
     * ```
     */
    public function __construct( string $key , string ...$keys )
    {
        $this->key  = $key ;
        $this->keys = [ $key , ...$keys ] ;
    }
}