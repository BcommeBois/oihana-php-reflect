<?php

namespace oihana\reflect\helpers;

use ReflectionClass;

/**
 * Check if a class uses a specific trait, including traits from parent classes
 * and nested traits, with optional external cache.
 *
 * When recursive mode is enabled (default), the function searches through:
 * - Direct traits of the class
 * - Traits used by other traits (nested traits)
 * - Traits from parent classes
 *
 * @param ReflectionClass    $reflectionClass The reflection of the class to inspect
 * @param string             $traitName       The fully qualified trait name to search for
 * @param bool               $recursive       Whether to recursively search in traits and parent classes
 * @param array<string,bool> $cache           Optional external cache (passed by reference)
 *
 * @return bool True if the class uses the trait, false otherwise
 *
 * @example
 * ### Basic recursive usage
 * ```php
 * trait MyTrait
 * {
 *     public $value;
 * }
 *
 * class MyParent
 * {
 *     use MyTrait;
 * }
 *
 * class MyClass extends MyParent
 * {
 *     public $name;
 * }
 *
 * $reflection = new ReflectionClass(MyClass::class);
 *
 * // Will find MyTrait in parent class
 * var_dump(hasTrait($reflection, MyTrait::class));
 * // bool(true)
 * ```
 *
 * @example
 * ### Non-recursive usage
 * ```php
 * // Using the same classes as above...
 * $reflection = new ReflectionClass(MyClass::class);
 *
 * // Will NOT find MyTrait because it's only in the parent
 * var_dump(hasTrait($reflection, MyTrait::class, false));
 * // bool(false)
 *
 * $parentReflection = new ReflectionClass(MyParent::class);
 *
 * // Will find MyTrait directly in MyParent
 * var_dump(hasTrait($parentReflection, MyTrait::class, false));
 * // bool(true)
 * ```
 *
 * @example
 * ### Nested traits detection
 * ```php
 * trait BaseTrait
 * {
 *     public $id;
 * }
 *
 * trait ComposedTrait
 * {
 *     use BaseTrait;
 *     public $name;
 * }
 *
 * class MyClass
 * {
 *     use ComposedTrait;
 * }
 *
 * $reflection = new ReflectionClass(MyClass::class);
 *
 * // Will find BaseTrait even though it's nested in ComposedTrait
 * var_dump(hasTrait($reflection, BaseTrait::class));
 * // bool(true)
 *
 * var_dump(hasTrait($reflection, ComposedTrait::class));
 * // bool(true)
 * ```
 *
 * @example
 * ### Using external cache for performance
 * ```php
 * $cache = [];
 * $reflection1 = new ReflectionClass(MyClass1::class);
 * $reflection2 = new ReflectionClass(MyClass2::class);
 *
 * // First call populates cache
 * hasTrait($reflection1, MyTrait::class, true, $cache);
 *
 * // Second call uses cache if classes overlap in hierarchy
 * hasTrait($reflection2, MyTrait::class, true, $cache);
 * ```
 *
 * @package oihana\reflect\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
function hasTrait
(
    ReflectionClass $reflectionClass,
    string          $traitName ,
    bool            $recursive = true,
    array           &$cache    = []
)
: bool
{
    $className = $reflectionClass->getName();
    $cacheKey  = $className . '::' . $traitName . '::' . ( $recursive ? '1' : '0' ) ;

    if ( isset( $cache[ $cacheKey ] ) )
    {
        return $cache[ $cacheKey ] ;
    }

    // Check direct traits of the current class
    $traits = $reflectionClass->getTraitNames() ;
    if ( in_array( $traitName , $traits , true ) )
    {
        $cache[ $cacheKey ] = true ;
        return true ;
    }

    if ( $recursive )
    {
        // Check traits used by traits (nested traits)
        foreach ( $reflectionClass->getTraits() as $trait )
        {
            if ( hasTrait( $trait, $traitName, true, $cache ) )
            {
                $cache[$cacheKey] = true;
                return true;
            }
        }

        // Check parent class
        $parent = $reflectionClass->getParentClass() ;
        if ( $parent && hasTrait($parent, $traitName, true, $cache ) )
        {
            $cache[ $cacheKey ] = true ;
            return true ;
        }
    }

    $cache[ $cacheKey ] = false ;
    return false;
}