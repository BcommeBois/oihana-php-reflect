<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration for function options.
 *
 * - Provides constants for 'function' and 'arguments' definitions.
 *
 * @package oihana\reflect\enums
 * @author  Marc Alcaraz
 * @since   1.0.3
 */
class FunctionEnum
{
    use ConstantsTrait ;

    /**
     * The function 'arguments' definition.
     */
    public const string ARGUMENTS = 'arguments' ;

    /**
     * The 'function' name definition.
     */
    public const string FUNCTION = 'function' ;
}
