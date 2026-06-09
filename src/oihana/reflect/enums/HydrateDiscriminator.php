<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the discriminator keys used to pick a target class when hydrating
 * polymorphic arrays of objects (see {@see \oihana\reflect\attributes\HydrateWith}).
 *
 * When an array item carries one of these keys, its value is matched against the short
 * or fully-qualified name of the candidate classes.
 *
 * @package oihana\reflect\enums
 * @author  Marc Alcaraz
 * @since   1.0.4
 */
class HydrateDiscriminator
{
    use ConstantsTrait ;

    /**
     * JSON-LD style discriminator using the literal `@type` key.
     */
    public const string AT_TYPE = '@type' ;

    /**
     * Camel-cased discriminator key `atType`.
     */
    public const string ATYPE = 'atType' ;

    /**
     * Plain `type` discriminator key.
     */
    public const string TYPE = 'type' ;

    /**
     * The discriminator keys, in resolution order.
     *
     * @return array<int, string>
     */
    public static function keys() : array
    {
        return [ self::ATYPE , self::AT_TYPE , self::TYPE ] ;
    }
}
