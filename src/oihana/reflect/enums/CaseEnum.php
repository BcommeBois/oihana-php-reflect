<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration for case options.
 *
 * - Provides constants for 'upper' and 'lower' case usage.
 *
 * @package oihana\reflect\enums
 * @author  Marc Alcaraz
 * @since   1.0.3
 */
class CaseEnum
{
    use ConstantsTrait ;

    /**
     * The 'lower' case usage.
     */
    public const string LOWER = 'lower' ;

    /**
     * The 'upper' case usage.
     */
    public const string UPPER = 'upper' ;
}
