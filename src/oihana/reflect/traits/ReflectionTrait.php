<?php

namespace oihana\reflect\traits ;

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
     * Optionally compresses the array by removing properties with null values.
     *
     * @param object|string $class    The object instance or fully-qualified class name.
     * @param bool|callable $reduce   If true, null properties are omitted. If a callable,
     *                                it receives ($propertyName, $propertyValue) and should return true to keep the property.
     * @param array|null    $options  Optional default reducer configuration:
     *                                  - **clone** (bool)         If `true`, works on a cloned copy of the array. Original remains unchanged. *(default: false)*
     *                                  - **conditions** (callable|array<callable>) One or more callbacks: `(mixed $value): bool`. If any condition returns `true`, the value is removed. ( default: `fn($v) => is_null($v)` )*
     *                                  - **excludes** (string[])  List of keys to exclude from filtering, even if matched by a condition.
     *                                  - **recursive** (bool)     Whether to recursively compress nested arrays or objects. *(default: true)*
     *                                  - **depth** (int|null)     Maximum depth for recursion. `null` means no limit.
     *                                  - **throwable** (bool)     If `true`, throws `InvalidArgumentException` for invalid conditions. *(default: true)*
     *
     * @return array The associative array of property values.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * class Product { public string $name = 'Book'; public ?string $desc = null; public int $stock = 0; }
     *
     * // remove only null values
     * $helper->jsonSerializeFromPublicProperties(Product::class, true); // ['name' => 'Book', 'stock' => 0]
     *
     * // custom filter: keep only non-empty strings
     * $helper->jsonSerializeFromPublicProperties(Product::class, fn($k,$v) => is_string($v) && $v !== ''); // ['name' => 'Book']
     * ```
     */
    public function jsonSerializeFromPublicProperties
    (
        object|string $class   ,
        bool|callable $reduce  = false ,
        ?array        $options = []
    )
    :array
    {
        $object     = [] ;
        $properties = $this->getPublicProperties( $class ) ;

        foreach( $properties as $property )
        {
            $name = $property->getName();

            $object[ $name ] = $this->{ $name } ?? null ;
        }

        if ( $reduce === true )
        {
            $object = compress( $object , $options ) ;
        }
        else if ( is_callable( $reduce ) )
        {
            $object = array_filter($object, fn($v, $k) => $reduce( $k , $v ) , ARRAY_FILTER_USE_BOTH ) ;
        }

        return $object ;
    }
}