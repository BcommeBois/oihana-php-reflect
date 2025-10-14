<?php

namespace oihana\reflect\helpers;

use ReflectionClass;
use ReflectionProperty;

/**
 * Get all public non-static properties from a class, including traits and parent classes,
 * with optional external cache.
 *
 * Duplicate property names are overridden by the closest definition in the inheritance chain
 * (class > trait > parent class).
 *
 * @param ReflectionClass $reflectionClass The reflection of the class to inspect
 * @param bool            $recursive       Whether to recursively fetch properties from traits and parents
 * @param array           $cache           Optional external cache (passed by reference)
 *
 * @return array<string, ReflectionProperty> An associative array of property names to ReflectionProperty objects
 *
 * @example
 * ### Basic recursive usage
 * ```php
 * trait MyTrait
 * {
 *     public $traitProp;
 * }
 * class MyParent
 * {
 *     public $parentProp;
 * }
 * class MyClass extends MyParent
 * {
 *     use MyTrait;
 *     public $classProp;
 * }
 *
 * $reflection = new ReflectionClass(MyClass::class);
 * $props = getPublicProperties($reflection);
 *
 * // $props will contain ReflectionProperty objects for:
 * // 'classProp', 'traitProp', and 'parentProp'
 * print_r(array_keys($props));
 * // Array ( [0] => classProp [1] => traitProp [2] => parentProp )
 * ```
 *
 * @example
 * ### Non-recursive usage
 * ```php
 * // Using the same classes as above...
 * $reflection = new ReflectionClass(MyClass::class);
 * $props = getPublicProperties($reflection, false);
 *
 * // $props will *only* contain the property defined directly in MyClass:
 * // 'classProp'
 * print_r(array_keys($props));
 * // Array ( [0] => classProp )
 * ```
 *
 * @example
 * ### Property Override Precedence (Class > Trait > Parent)
 * ```php
 * trait OverridingTrait
 * {
 *     public $name = 'from trait';
 * }
 * class OverridingParent
 * {
 *     public $name = 'from parent';
 * }
 * class MyChild extends OverridingParent
 * {
 *     use OverridingTrait;
 *     public $name = 'from child'; // This one will be returned
 * }
 *
 * $reflection = new ReflectionClass(MyChild::class);
 * $props = getPublicProperties($reflection);
 * $prop = $props['name'];
 *
 * // The declaring class for 'name' will be MyChild, as it has the highest priority.
 * echo $prop->getDeclaringClass()->getName();
 * // MyChild
 * ```
 *
 * @package oihana\core\reflections
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.4
 */
function getPublicProperties
(
    ReflectionClass $reflectionClass ,
    bool            $recursive       = true ,
    array           &$cache          = []
)
:array
{
    $className = $reflectionClass->getName() ;

    if ( isset( $cache[ $className ] ) )
    {
        return $cache[ $className ] ;
    }

    $properties = [] ;

    $traitPropertyNames = [] ;
    foreach ($reflectionClass->getTraits() as $trait)
    {
        foreach ($trait->getProperties(ReflectionProperty::IS_PUBLIC) as $prop)
        {
            if (!$prop->isStatic())
            {
                $traitPropertyNames[$prop->getName()] = true;
            }
        }
    }

    foreach ($reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $property)
    {
        if (!$property->isStatic())
        {
            $propName = $property->getName();
            $declaringClass = $property->getDeclaringClass() ;

            if ( $declaringClass->getName() === $className && !isset( $traitPropertyNames[$propName] ) )
            {
                $properties[$propName] = $property ;
            }
        }
    }

    if ($recursive)
    {
        foreach ( $reflectionClass->getTraits() as $trait)
        {
            foreach ( getPublicProperties( $trait , true , $cache ) as $name => $property )
            {
                if ( !isset($properties[$name] ) )
                {
                    $properties[ $name ] = $property;
                }
            }
        }

        $parent = $reflectionClass->getParentClass() ;
        if ( $parent )
        {
            foreach ( getPublicProperties($parent, true, $cache ) as $name => $property )
            {
                if ( !isset($properties[$name]) )
                {
                    $properties[ $name ] = $property ;
                }
            }
        }
    }

    if( !empty( $properties ) )
    {
        ksort($properties );
    }

    $cache[ $className ] = $properties;

    return $properties;
}