<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Options understood by {@see \oihana\reflect\traits\ReflectionTrait::toArray()} that are
 * specific to this package (i.e. not part of the shared `oihana\core\options\ArrayOption`).
 *
 * @package oihana\reflect\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.5
 */
class SerializeOption
{
    use ConstantsTrait ;

    /**
     * `string` — the date format applied to `DateTimeInterface` property values
     * (default: `DateTimeInterface::ATOM`, i.e. ISO 8601).
     */
    public const string DATE_FORMAT = 'dateFormat' ;

    /**
     * `bool` — when true, emit each property under its `#[HydrateKey]` source key (the primary
     * one) instead of its property name, making `toArray()` symmetric with `hydrate()`.
     */
    public const string USE_HYDRATE_KEYS = 'useHydrateKeys' ;
}
