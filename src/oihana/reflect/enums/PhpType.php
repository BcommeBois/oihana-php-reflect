<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the PHP types.
 *
 * @package oihana\reflect\traits
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.4
 */
final class PhpType
{
    use ConstantsTrait ;

    /**
     * Arrays are used for ordered elements.
     */
    public const string ARRAY = 'array';

    /**
     * The boolean type matches only two special values: true and false.
     */
    public const string BOOLEAN = 'bool';

    /**
     * The float type is used for any numeric type, either integers or floating point numbers.
     * Alias of the {@see PhpType::NUMBER} constant.
     */
    public const string FLOAT = 'float';

    /**
     * The integer type is used for integral numbers.
     */
    public const string INTEGER = 'int';

    /**
     * The 'mixed' type (all).
     */
    public const string MIXED = 'mixed' ;

    /**
     * When a variable specifies a type of null, it has only one acceptable value: null.
     */
    public const string NULL = 'null';

    /**
     * The number type (float) is used for any numeric type, either integers or floating point numbers.
     * Alias of the {@see PhpType::FLOAT} constant.
     */
    public const string NUMBER = 'float' ;

    /**
     * Objects type. They map "keys" to "values".
     */
    public const string OBJECT = 'object' ;

    /**
     * The string type is used for strings of text. It may contain Unicode characters.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string
     */
    public const string STRING = 'string';

}