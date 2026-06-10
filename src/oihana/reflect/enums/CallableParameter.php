<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the keys describing a callable parameter.
 *
 * These constants name the fields of the descriptor arrays returned by
 * {@see \oihana\reflect\Reflection::describeCallableParameters()}.
 *
 * @package oihana\reflect\enums
 * @author  Marc Alcaraz
 * @since   1.1.0
 */
class CallableParameter
{
    use ConstantsTrait ;

    /**
     * The parameter default value (only present when one is available).
     */
    public const string DEFAULT = 'default' ;

    /**
     * The parameter name.
     */
    public const string NAME = 'name' ;

    /**
     * Whether the parameter accepts null.
     */
    public const string NULLABLE = 'nullable' ;

    /**
     * Whether the parameter is optional.
     */
    public const string OPTIONAL = 'optional' ;

    /**
     * The parameter type name (or null when untyped).
     */
    public const string TYPE = 'type' ;

    /**
     * Whether the parameter is variadic.
     */
    public const string VARIADIC = 'variadic' ;
}
