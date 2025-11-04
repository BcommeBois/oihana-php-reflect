<?php

namespace oihana\reflect\helpers;

use oihana\reflect\traits\ConstantsTrait;
use ReflectionClass;

/**
 * Checks if a given class uses the `ConstantsTrait`, either directly or via parent classes.
 *
 * This function leverages {@see hasTrait()} to perform a recursive search:
 * - Checks if the class itself uses `ConstantsTrait`
 * - Checks traits used by traits (nested traits)
 * - Checks parent classes recursively
 *
 * @param string $className The fully-qualified class name to inspect.
 * @param array  $cache     Optional external cache passed by reference to speed up repeated checks.
 *
 * @return bool Returns `true` if the class or any parent class uses `ConstantsTrait`, `false` otherwise.
 *
 * @example
 * ```php
 * use oihana\reflect\helpers\useConstantsTrait;
 * use oihana\reflect\traits\ConstantsTrait;
 *
 * trait MyEnumTrait { use ConstantsTrait; }
 *
 * class ParentClass { use MyEnumTrait; }
 * class ChildClass extends ParentClass {}
 * class NoTraitClass {}
 *
 * // Direct usage
 * var_dump(useConstantsTrait(ParentClass::class)); // true
 *
 * // Inherited via parent
 * var_dump(useConstantsTrait(ChildClass::class)); // true
 *
 * // Class without the trait
 * var_dump(useConstantsTrait(NoTraitClass::class)); // false
 *
 * // Non-existent class
 * var_dump(useConstantsTrait('NonExistentClass')); // false
 * ```
 *
 * @package oihana\reflect\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
function useConstantsTrait( string $className , array &$cache = [] ): bool
{
    if ( !class_exists( $className ) )
    {
        return false ;
    }

    $reflection = new ReflectionClass( $className ) ;

    return hasTrait( $reflection , ConstantsTrait::class , true , $cache ) ;
}