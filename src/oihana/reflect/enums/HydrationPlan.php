<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the keys describing a cached hydration plan.
 *
 * These constants name the fields of the plan arrays built by
 * {@see \oihana\reflect\Reflection::buildHydrationPlan()} and consumed by
 * {@see \oihana\reflect\Reflection::hydrate()}.
 *
 * @package oihana\reflect\enums
 * @author  Marc Alcaraz
 * @since   1.0.4
 */
class HydrationPlan
{
    use ConstantsTrait ;

    /**
     * Whether the property type allows null.
     */
    public const string ALLOWS_NULL = 'allowsNull' ;

    /**
     * The resolved {@see \oihana\reflect\attributes\HydrateAs} target class (or null).
     */
    public const string AS = 'as' ;

    /**
     * The builtin scalar type names declared on the property.
     */
    public const string BUILTINS = 'builtins' ;

    /**
     * Whether the constructor must be bypassed (it declares required arguments).
     */
    public const string BYPASS_CONSTRUCTOR = 'bypassConstructor' ;

    /**
     * The PHPDoc `@var` array-item class resolved once (or null).
     */
    public const string DOC_ITEM = 'docItem' ;

    /**
     * Whether the property declares a type.
     */
    public const string HAS_TYPE = 'hasType' ;

    /**
     * Whether the property is publicly readable (and therefore assigned).
     */
    public const string IS_PUBLIC = 'isPublic' ;

    /**
     * The source key used to read the value from the data ({@see \oihana\reflect\attributes\HydrateKey}).
     */
    public const string KEY = 'key' ;

    /**
     * The list of per-property plan descriptors.
     */
    public const string PROPERTIES = 'properties' ;

    /**
     * The ReflectionProperty instance.
     */
    public const string PROPERTY = 'property' ;

    /**
     * The declared property types (ReflectionNamedType list).
     */
    public const string TYPES = 'types' ;

    /**
     * The resolved {@see \oihana\reflect\attributes\HydrateWith} candidate classes (or null).
     */
    public const string WITH = 'with' ;
}
