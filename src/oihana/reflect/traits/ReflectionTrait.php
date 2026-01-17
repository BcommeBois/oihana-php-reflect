<?php

namespace oihana\reflect\traits ;

use oihana\reflect\options\JsonSerializeOption;
use ReflectionException;
use ReflectionProperty;

use oihana\reflect\Reflection;

use function oihana\core\arrays\compress;

/**
 * Provides helper methods for working with PHP reflection.
 *
 * This trait wraps around {@see Reflection} to simplify:
 * - Retrieving constants, properties, and method parameter metadata
 * - Checking parameter characteristics (type, default value, nullability, optionality, variadic)
 * - Hydrating objects from arrays (optionally using attributes such as {@see HydrateAs}, {@see HydrateKey}, {@see HydrateWith})
 * - Serializing public properties to arrays for JSON output
 *
 * It can be used in any class that needs reflection-based utilities without directly instantiating `Reflection` everywhere.
 *
 * @package oihana\reflect\traits
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.0
 */
trait ReflectionTrait
{
    /**
     * Cached Reflection instance for performance.
     */
    private ?Reflection $__reflection = null ;

    /**
     * Cached short class name for performance.
     */
    private ?string $__shortName  = null ;

    /**
     * Get the internal Reflection instance.
     * @return Reflection
     */
    public function reflection(): Reflection
    {
        return $this->__reflection ??= new Reflection() ;
    }

    /**
     * Returns all constants of a given class or object.
     *
     * @param object|string $class The object instance or fully-qualified class name.
     *
     * @return array An associative array of constant names to values.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class MyConstants
     * {
     *     public const FOO = 'bar';
     * }
     * $trait->getConstants(MyConstants::class); // ['FOO' => 'bar']
     * ```
     */
    public function getConstants( object|string $class ) : array
    {
        return $this->reflection()->constants( $class ) ;
    }

    /**
     * Returns all public properties of a given class or object.
     *
     * @param object|string $class The object instance or fully-qualified class name.
     *
     * @return ReflectionProperty[] An array of ReflectionProperty objects.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class User
     * {
     *     public string $name;
     * }
     * $trait->getPublicProperties(User::class); // [ReflectionProperty{name: 'name'}]
     * ```
     */
    public function getPublicProperties( object|string $class ) : array
    {
        return $this->reflection()->properties($class);
    }

    /**
     * Returns the short (unqualified) class name of a given class or object.
     *
     * @param object|string $class The object instance or fully-qualified class name.
     *
     * @return string The short class name.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * $trait->getShortName(\App\Models\User::class); // 'User'
     * ```
     */
    public function getShortName( object|string $class ) : string
    {
        return $this->__shortName ??= $this->reflection()->shortName($class);
    }

    /**
     * Returns all parameters of a given method as ReflectionParameter objects.
     *
     * @param object|string $class  The object instance or fully-qualified class name.
     * @param string        $method The method name.
     *
     * @return array An array of ReflectionParameter objects.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class Foo { public function bar(string $x, int $y = 0) {} }
     * $trait->getMethodParameters(Foo::class, 'bar');
     * // [ReflectionParameter{name: 'x'}, ReflectionParameter{name: 'y'}]
     * ```
     */
    public function getMethodParameters( object|string $class, string $method ): array
    {
        return $this->reflection()->parameters($class, $method);
    }

    /**
     * Returns the type name of a given method parameter, or null if none is declared.
     *
     * @param object|string $class  The object instance or fully-qualified class name.
     * @param string        $method The method name.
     * @param string        $param  The parameter name.
     *
     * @return string|null The parameter type name or null.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class Foo { public function bar(string $x) {} }
     * $trait->getParameterType(Foo::class, 'bar', 'x'); // 'string'
     * ```
     */
    public function getParameterType(object|string $class, string $method, string $param): ?string
    {
        return $this->reflection()->parameterType($class, $method, $param);
    }

    /**
     * Returns the default value of a given method parameter, or null if none is set.
     *
     * @param object|string $class The object instance or fully-qualified class name.
     *
     * @param string $method The method name.
     * @param string $param  The parameter name.
     *
     * @return mixed|null The default value or null.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class Foo { public function bar(string $x = 'abc') {} }
     * $trait->getParameterDefaultValue(Foo::class, 'bar', 'x'); // 'abc'
     * ```
     */
    public function getParameterDefaultValue(object|string $class, string $method, string $param): mixed
    {
        return $this->reflection()->parameterDefaultValue($class, $method, $param);
    }

    /**
     * Checks if a given method has a specific parameter.
     *
     * @param object|string $class  The object instance or fully-qualified class name.
     * @param string        $method The method name.
     * @param string        $param  The parameter name.
     *
     * @return bool True if the parameter exists, false otherwise.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class Foo { public function bar(string $x) {} }
     * $trait->hasParameter(Foo::class, 'bar', 'x'); // true
     * $trait->hasParameter(Foo::class, 'bar', 'z'); // false
     * ```
     */
    public function hasParameter(object|string $class, string $method, string $param): bool
    {
        return $this->reflection()->hasParameter($class, $method, $param);
    }

    /**
     * Checks if a given method parameter is nullable.
     *
     * @param object|string $class  The object instance or fully-qualified class name.
     * @param string        $method The method name.
     * @param string        $param  The parameter name.
     *
     * @return bool True if nullable, false otherwise.
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class Foo { public function bar(?string $x) {} }
     * $trait->isParameterNullable(Foo::class, 'bar', 'x'); // true
     * ```
     */
    public function isParameterNullable( object|string $class, string $method, string $param ): bool
    {
        return $this->reflection()->isParameterNullable( $class , $method , $param );
    }

    /**
     * Checks if a given method parameter is optional.
     *
     * @param object|string $class  The object instance or fully-qualified class name.
     * @param string        $method The method name.
     * @param string        $param  The parameter name.
     *
     * @return bool True if optional, false otherwise.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class Foo { public function bar(string $x = 'abc') {} }
     * $trait->isParameterOptional(Foo::class, 'bar', 'x'); // true
     * ```
     */
    public function isParameterOptional( object|string $class, string $method, string $param ): bool
    {
        return $this->reflection()->isParameterOptional( $class , $method , $param ) ;
    }

    /**
     * Checks if a given method parameter is variadic.
     *
     * @param object|string $class  The object instance or fully-qualified class name.
     * @param string        $method The method name.
     * @param string        $param  The parameter name.
     *
     * @return bool True if variadic, false otherwise.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class Foo { public function bar(string ...$x) {} }
     * $trait->isParameterVariadic(Foo::class, 'bar', 'x'); // true
     * ```
     */
    public function isParameterVariadic(object|string $class, string $method, string $param): bool
    {
        return $this->reflection()->isParameterVariadic($class, $method, $param);
    }

    /**
     * Creates and hydrates an object of the given class from an associative array.
     *
     * Uses {@see Reflection::hydrate()} and supports attributes such as:
     * - {@see HydrateAs}
     * - {@see HydrateKey}
     * - {@see HydrateWith}
     *
     * @param array  $thing The data array.
     * @param string $class The fully-qualified class name to instantiate.
     *
     * @return object The hydrated object.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class User { public string $name; }
     * $trait->hydrate(['name' => 'Alice'], User::class); // User{name: 'Alice'}
     * ```
     */
    public function hydrate( array $thing , string $class ): object
    {
        return $this->reflection()->hydrate( $thing , $class ) ;
    }

    /**
     * Generates an associative array from the public properties of a given class or object.
     *
     * This method supports fine-grained control over the serialization:
     * - filtering properties via `INCLUDE` / `EXCLUDE`,
     * - reducing values (removing nulls or applying a custom callable),
     * - injecting keys before or after the serialized properties (`BEFORE` / `AFTER`),
     * - reordering keys via `FIRST_KEYS`,
     * - sorting remaining keys alphabetically (`SORT`).
     *
     * @param object|string $class   The object instance or fully-qualified class name.
     * @param array|null    $options Optional configuration array (see {@see JsonSerializeOption}):
     *                               - **REDUCE**      (bool|array|callable)
     *                                                 If `true`, null values are removed.
     *                                                 If array, forwarded to `compress()` with options.
     *                                                 If callable: `fn($propertyName, $propertyValue): bool`,
     *                                                 return `true` to keep the property.
     *                               - **INCLUDE**     (string[]|null) Whitelist of property names to include.
     *                               - **EXCLUDE**     (string[]|null) Blacklist of property names to exclude.
     *                               - **BEFORE**      (array<string,mixed>) Keys/values prepended before serialized properties.
     *                               - **AFTER**       (array<string,mixed>) Keys/values appended after serialized properties.
     *                               - **FIRST_KEYS**  (string[]) Keys that must appear first in the resulting array (in order).
     *                               - **SORT**        (bool) Whether remaining keys are sorted alphabetically (default: true).
     *
     * @return array The associative array representing the public properties of the object.
     *
     * @throws ReflectionException
     *
     * @example Basic usage
     * ```php
     * class Product
     * {
     *     public string $name = 'Book';
     *     public ?string $desc = null;
     *     public int $stock = 0;
     * }
     *
     * // Remove null properties
     * $helper->jsonSerializeFromPublicProperties( Product::class,
     * [
     *     JsonSerializeOption::REDUCE => true
     * ]);
     * // Result: ['name' => 'Book', 'stock' => 0]
     *
     * // Custom filter: keep only non-empty strings
     * $helper->jsonSerializeFromPublicProperties( Product::class,
     * [
     *     JsonSerializeOption::REDUCE => fn($k, $v) => is_string($v) && $v !== ''
     * ]);
     * // Result: ['name' => 'Book']
     *
     * // Inject metadata and reorder keys
     * $helper->jsonSerializeFromPublicProperties( Product::class,
     * [
     *     JsonSerializeOption::BEFORE      => ['_type' => 'Product'],
     *     JsonSerializeOption::FIRST_KEYS  => ['_type', 'name'],
     *     JsonSerializeOption::REDUCE      => true
     * ]);
     * ```
     */
    public function jsonSerializeFromPublicProperties
    (
        object|string $class   ,
        ?array        $options = []
    )
    :array
    {
        $data       = [] ;
        $options    = JsonSerializeOption::normalize( $options ) ;
        $properties = $this->getPublicProperties( $class ) ;

        foreach ( $properties as $property)
        {
            $name = $property->getName() ;

            if
            (
                    is_array( $options[ JsonSerializeOption::INCLUDE ] )
                && !in_array( $name , $options[ JsonSerializeOption::INCLUDE ] , true )
            )
            {
                continue ;
            }

            if
            (
                   is_array( $options[ JsonSerializeOption::EXCLUDE ] )
                && in_array( $name , $options[ JsonSerializeOption::EXCLUDE ] , true )
            )
            {
                continue ;
            }

            $data[ $name ] = $this->{ $name } ?? null ;
        }

        $reduce = $options[ JsonSerializeOption::REDUCE ] ;
        $data = match( true )
        {
            $reduce     === true    => compress( $data ) ,
            is_array    ( $reduce ) => compress( $data , $reduce ) ,
            is_callable ( $reduce ) => array_filter( $data , fn($v, $k) => $reduce($k, $v) , ARRAY_FILTER_USE_BOTH ) ,
            default                 => $data ,
        };

        if ( $options[ JsonSerializeOption::BEFORE ] )
        {
            $data = $options[ JsonSerializeOption::BEFORE ] + $data ;
        }

        if ($options[ JsonSerializeOption::AFTER ] )
        {
            $data += $options[ JsonSerializeOption::AFTER ] ;
        }

        if ( $options[ JsonSerializeOption::FIRST_KEYS ] )
        {
            $ordered = [] ;
            foreach ( $options[ JsonSerializeOption::FIRST_KEYS ] as $key )
            {
                if ( array_key_exists( $key , $data ) )
                {
                    $ordered[ $key ] = $data[ $key ] ;
                    unset( $data[ $key ] ) ;
                }
            }

            if ( $options[ JsonSerializeOption::SORT ] )
            {
                ksort($data , SORT_STRING ) ;
            }

            return $ordered + $data ;
        }

        if ( $options[ JsonSerializeOption::SORT ] )
        {
            ksort($data, SORT_STRING ) ;
        }

        return $data ;
    }
}