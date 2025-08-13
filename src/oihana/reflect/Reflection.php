<?php

namespace oihana\reflect;

use Closure;
use InvalidArgumentException;

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;

use oihana\reflect\attributes\HydrateAs;
use oihana\reflect\attributes\HydrateKey;
use oihana\reflect\attributes\HydrateWith;

use function oihana\core\arrays\isAssociative;

/**
 * High-level helper over PHP's Reflection API and a robust array-to-object hydrator.
 *
 * Responsibilities:
 * - Introspect classes: list constants, methods and properties with visibility filters
 * - Inspect method parameters: type, default value, nullability, optionality, variadic
 * - Describe any callable's parameters (closures, functions, methods, and invokable objects)
 * - Hydrate objects from associative arrays (recursively), including arrays of objects
 *
 * Hydration features:
 * - Honors union types and nullability
 * - Attribute-based mapping support:
 *   - {@see HydrateKey} to map incoming keys to a different property name
 *   - {@see HydrateWith} to hydrate arrays of objects (supports polymorphic items)
 *   - {@see HydrateAs} to enforce a target class for ambiguous property types
 * - PHPDoc support for array element types via `@var Type[]` and `@var array<Type>`
 * - Assigns public properties only (private/protected are ignored by design)
 *
 * Caching:
 * - Internally caches {@see \ReflectionClass} instances per fully-qualified class name for better performance
 *
 * Limitations and notes:
 * - Hydration relies on property names (or aliases via attributes) and only sets public properties
 * - When multiple target classes are provided in {@see HydrateWith}, selection is based on
 *   an explicit discriminator (`@type` or `type`) or by best-guess using matching property names
 *
 * @package oihana\reflect
 * @see ReflectionTrait Convenience trait wrapping this class in userland objects
 * @see ConstantsTrait Constants utilities available in this package
 * @see HydrateKey
 * @see HydrateWith
 * @see HydrateAs
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.0
 */
class Reflection
{
    /**
     * Returns an array of constants defined in the given class.
     *
     * @param object|string $class The object or class name.
     * @param int $filter A bitmask of constant visibility (default: public).
     * @return array<string, mixed> Associative array of constant names and values.
     * @throws ReflectionException If reflection fails.
     *
     * @example
     * ```php
     * class MyStatus {
     *     public const ACTIVE = 'active';
     *     private const SECRET = 'hidden';
     * }
     *
     * $constants = (new Reflection())->constants(MyStatus::class);
     * print_r($constants);
     * // Output: ['ACTIVE' => 'active']
     * ```
     */
    public function constants( object|string $class, int $filter = ReflectionClassConstant::IS_PUBLIC ): array
    {
        return $this->reflection( $class )->getConstants( $filter );
    }

    /**
     * Returns a detailed description of parameters for any valid PHP callable.
     *
     * @param callable|string|array $callable Any valid PHP callable (Closure, function name, method array, etc.).
     *
     * @return array<int, array<string, mixed>> Each entry contains:
     *         - name      : string
     *         - type      : string|null
     *         - optional  : bool
     *         - nullable  : bool
     *         - variadic  : bool
     *         - default   : mixed|null (if available)
     *
     * @throws ReflectionException
     * @throws InvalidArgumentException If the callable is invalid.
     *
     * @example
     * ```php
     * $ref = new \oihana\reflect\Reflection();
     * print_r($ref->describeCallableParameters([MyClass::class, 'doSomething']));
     *
     * print_r($ref->describeCallableParameters('array_map'));
     *
     * $fn = fn(string $name, int $age = 42) => "$name is $age";
     * print_r($ref->describeCallableParameters($fn));
     *
     * class Greeter
     * {
     *     public function __invoke(string $name) {}
     * }
     * print_r($ref->describeCallableParameters(new Greeter()));
     * ```
     */
    public function describeCallableParameters( callable|string|array $callable ): array
    {
        if ( is_array( $callable ) )
        {
            $reflection = new ReflectionMethod( $callable[0], $callable[1] );
        }
        elseif ( is_string( $callable ) && str_contains( $callable , '::' ) )
        {
            [ $class, $method ] = explode( '::', $callable );
            $reflection = new ReflectionMethod( $class, $method );
        }
        elseif ( is_string( $callable ) )
        {
            $reflection = new ReflectionFunction( $callable );
        }
        elseif ( $callable instanceof Closure )
        {
            $reflection = new ReflectionFunction( $callable );
        }
        elseif ( is_object( $callable ) && method_exists( $callable, '__invoke' ) )
        {
            $reflection = new ReflectionMethod( $callable, '__invoke' );
        }
        else
        {
            throw new InvalidArgumentException('Unsupported callable type.');
        }

        $result = [];

        foreach ( $reflection->getParameters() as $p )
        {
            $type      = $p->getType();
            $typeName  = null;
            $nullable  = false;

            if ( $type instanceof ReflectionUnionType )
            {
                $types = [];
                foreach ( $type->getTypes() as $t )
                {
                    $types[] = $t->getName();
                    if ( $t->getName() === 'null' )
                    {
                        $nullable = true;
                    }
                }
                $typeName = implode('|', $types);
            }
            elseif ( $type instanceof \ReflectionNamedType )
            {
                $typeName = $type->getName();
                $nullable = $type->allowsNull();
            }

            $paramData =
            [
                'name'     => $p->getName(),
                'type'     => $typeName,
                'optional' => $p->isOptional(),
                'nullable' => $nullable,
                'variadic' => $p->isVariadic(),
            ];

            if ( $p->isDefaultValueAvailable() )
            {
                $paramData['default'] = $p->getDefaultValue();
            }

            $result[] = $paramData;
        }

        return $result;
    }

    /**
     * Checks if the specified method has a parameter with the given name.
     *
     * @param object|string $class  The class name or object.
     * @param string        $method The method name.
     * @param string        $param  The parameter name to check.
     *
     * @return bool True if the parameter exists, false otherwise.
     * @throws ReflectionException If the method cannot be reflected.
     *
     * @example
     * ```php
     * $has = (new Reflection())->hasParameter(MyClass::class, 'myMethod', 'input');
     * ```
     */
    public function hasParameter( object|string $class, string $method, string $param ): bool
    {
        $parameters = $this->parameters( $class , $method ) ;
        return array_any( $parameters , fn( $p ) => $p->getName() === $param );
    }

    /**
     * Instantiates and hydrates an object of a given class using associative array data.
     *
     * It supports:
     * - Recursive hydration for nested objects (flat or array),
     * - Union types (e.g., `Type|null`),
     * - Custom source keys with `#[HydrateKey]`,
     * - Array hydration via `#[HydrateWith]`, `#[HydrateAs]`, or PHPDoc `@ var Type[]`,
     * - Public properties only (private/protected are ignored).
     *
     * @param array  $thing Associative array of data (keys must match public properties or be aliased via attributes).
     * @param string $class Fully qualified class name of the object to instantiate.
     *
     * @return object The hydrated object instance.
     *
     * @throws InvalidArgumentException If the class does not exist or required non-nullable property is null.
     * @throws ReflectionException If property introspection fails.
     *
     * @example Flat object hydration
     * ```php
     * class User {
     *     public string $name;
     * }
     *
     * $data = ['name' => 'Alice'];
     * $user = (new Reflection())->hydrate($data, User::class);
     * echo $user->name; // "Alice"
     * ```
     *
     * @example Nested object hydration
     * ```php
     * class Address {
     *     public string $city;
     * }
     * class User {
     *     public string $name;
     *     public ?Address $address = null;
     * }
     *
     * $data = ['name' => 'Alice', 'address' => ['city' => 'Paris']];
     * $user = (new Reflection())->hydrate($data, User::class);
     * echo $user->address->city; // "Paris"
     * ```
     *
     * @example Hydration with `#[HydrateKey]`
     * ```php
     * use oihana\reflect\attributes\HydrateKey;
     *
     * class User {
     *     #[HydrateKey('user_name')]
     *     public string $name;
     * }
     *
     * $data = ['user_name' => 'Bob'];
     * $user = (new Reflection())->hydrate($data, User::class);
     * echo $user->name; // "Bob"
     * ```
     *
     * @example Hydration of array of objects via `#[HydrateWith]`
     * ```php
     * use oihana\reflect\attributes\HydrateWith;
     *
     * class Address {
     *     public string $city;
     * }
     * class Geo {
     *     #[HydrateWith(Address::class)]
     *     public array $locations = [];
     * }
     *
     * $data = ['locations' => [['city' => 'Paris'], ['city' => 'Berlin']]];
     * $geo = (new Reflection())->hydrate($data, Geo::class);
     * echo $geo->locations[1]->city; // "Berlin"
     * ```
     *
     * @example Hydration of array via `@var Type[]`
     * ```php
     * class Address
     * {
     *     public string $city;
     * }
     *
     * class Geo
     * {
     *     / ** @ var Address[] * /
     *     public array $locations = [];
     * }
     *
     * $data = ['locations' => [['city' => 'Lyon'], ['city' => 'Nice']]];
     * $geo = (new Reflection())->hydrate($data, Geo::class);
     * echo $geo->locations[0]->city; // "Lyon"
     * ```
     *
     * @example Hydration of array via `@var array<Address>`
     * ```php
     * class Address
     * {
     *     public string $city;
     * }
     *
     * class Geo
     * {
     *     / ** @ var array<Address> * /
     *     public array $locations = [];
     * }
     * ```
     *
     * @example Union types
     * ```php
     * class Profile {
     *     public ?string $bio = null;
     * }
     *
     * $data = ['bio' => null];
     * $profile = ( new Reflection() )->hydrate( $data , Profile::class ) ;
     * var_dump($profile->bio); // null
     * ```
     */
    public function hydrate( array $thing , string $class ): object
    {
        if ( !class_exists( $class ) )
        {
            throw new InvalidArgumentException("hydrate failed, the class '$class' does not exist.");
        }

        $reflectionClass = $this->reflection( $class ) ;
        $object          = new $class() ;
        $properties      = $reflectionClass->getProperties() ;

        foreach ( $properties as $property)
        {
            // Determines the key to be used in $thing (via #[HydrateKey])
            $propertyKey = $property->getName() ;
            $keyAttr     = $property->getAttributes(HydrateKey::class ) ;

            if ( !empty( $keyAttr ) )
            {
                $propertyKey = $keyAttr[0]->newInstance()->key ;
            }

            if ( !array_key_exists( $propertyKey , $thing ) )
            {
                continue;
            }

            $value = $thing[ $propertyKey ] ;

            if ( $property->hasType() )
            {
                $propertyType = $property->getType() ;

                $types = $propertyType instanceof ReflectionUnionType
                       ? $propertyType->getTypes()
                       : [ $propertyType ] ;

                $hydrated = false ;

                foreach ( $types as $type )
                {
                    $typeName = $type->getName();

                    if ( $typeName === 'null' && $value === null )
                    {
                        break;
                    }

                    // Attribut #[HydrateAs(Foo::class)]
                    $hydrateAs = $property->getAttributes(HydrateAs::class ) ;
                    if ( !empty( $hydrateAs ) )
                    {
                        $typeName = $hydrateAs[0]->newInstance()->class ;
                    }

                    if ( $typeName === 'array' && is_array( $value ) )
                    {
                        // 1. #[HydrateWith(MyClass::class, AnotherClass::class)]
                        $hydrateWith = $property->getAttributes(HydrateWith::class ) ;
                        if ( !empty( $hydrateWith ) )
                        {
                            $possibleClasses = $hydrateWith[0]->newInstance()->classes ;
                            $hydratedArray   = [];

                            foreach ( $value as $item )
                            {
                                if ( is_array( $item ) )
                                {
                                    $itemClass = $this->determineArrayItemType($item, $possibleClasses) ;
                                    if ( $itemClass && class_exists( $itemClass ) )
                                    {
                                        $hydratedArray[] = $this->hydrate($item, $itemClass);
                                    }
                                    else
                                    {
                                        $hydratedArray[] = $item ; // Do nothing
                                    }
                                }
                                else
                                {
                                    $hydratedArray[] = $item ;
                                }
                            }

                            $value    = $hydratedArray;
                            $hydrated = true;
                            break;
                        }

                        // 2. DocComment analysis: @var MyClass | @var \oihana\package\MyClass | @var array<MockAddress>
                        $doc = $property->getDocComment() ;
                        if
                        (
                            $doc &&
                            preg_match('/@var\s+((\w+(?:\\\\\w+)*)\[\]|array<(\w+(?:\\\\\w+)*)>)/' , $doc , $matches )
                        )
                        {
                            $itemClass = $matches[1] ?: $matches[2]; // Support both Type[] and array<Type>
                            if ( class_exists( $itemClass ) )
                            {
                                $value    = array_map( fn( $v ) => is_array( $v ) ? $this->hydrate( $v , $itemClass ) : $v , $value );
                                $hydrated = true;
                                break;
                            }
                        }
                    }
                    else if ( class_exists( $typeName ) )
                    {
                        if ( is_array( $value ) )
                        {
                            $value = isAssociative( $value )
                                   ? $this->hydrate( $value , $typeName )
                                   : array_map( fn( $v ) => $this->hydrate( $v , $typeName ) , $value ) ;
                            $hydrated = true ;
                            break;
                        }
                    }
                }

                if ( !$hydrated && $value === null && !$property->getType()->allowsNull() )
                {
                    throw new InvalidArgumentException("Property {$property->getName()} does not allow null" ) ;
                }
            }

            if ( $property->isPublic() )
            {
                $object->{ $property->getName() } = $value;
            }
        }

        return $object;
    }

    /**
     * Checks if a parameter is nullable (has ?Type or union with null).
     *
     * @param object|string $class  The class name or object instance.
     * @param string        $method The method name.
     * @param string        $param  The parameter name to check.
     *
     * @return bool True if the parameter type allows null, false otherwise.
     * @throws ReflectionException If the method does not exist or reflection fails.
     *
     * @example
     * ```php
     * class Example {
     *     public function demo(?string $name, int $age) {}
     * }
     *
     * $ref = new \oihana\reflect\Reflection();
     *
     * var_dump($ref->isParameterNullable(Example::class, 'demo', 'name')); // bool(true)
     * var_dump($ref->isParameterNullable(Example::class, 'demo', 'age'));  // bool(false)
     * ```
     */
    public function isParameterNullable( object|string $class, string $method, string $param ): bool
    {
        $parameters = $this->parameters( $class , $method ) ;
        foreach ( $parameters as $p )
        {
            if ( $p->getName() === $param && $p->hasType() )
            {
                return $p->getType()->allowsNull();
            }
        }
        return false;
    }

    /**
     * Checks if a given parameter in a method is optional (has a default value or is nullable).
     *
     * @param object|string $class  The class name or object instance.
     * @param string        $method The method name.
     * @param string        $param  The parameter name to check.
     *
     * @return bool True if the parameter is optional, false otherwise.
     * @throws ReflectionException If the method does not exist or reflection fails.
     *
     * @example
     * ```php
     * class Example {
     *     public function demo(string $name, int $age = 30, ?string $nickname = null) {}
     * }
     *
     * $ref = new \oihana\reflect\Reflection();
     *
     * var_dump($ref->isParameterOptional(Example::class, 'demo', 'name'));     // bool(false)
     * var_dump($ref->isParameterOptional(Example::class, 'demo', 'age'));      // bool(true)
     * var_dump($ref->isParameterOptional(Example::class, 'demo', 'nickname')); // bool(true)
     * ```
     */
    public function isParameterOptional( object|string $class, string $method, string $param ): bool
    {
        $parameters = $this->parameters( $class , $method ) ;
        foreach ( $parameters as $p )
        {
            if ( $p->getName() === $param )
            {
                return $p->isOptional();
            }
        }
        return false;
    }

    /**
     * Checks if a given parameter in a method is variadic (e.g., ...$args).
     *
     * @param object|string $class  The class name or object instance.
     * @param string        $method The method name.
     * @param string        $param  The parameter name to check.
     *
     * @return bool True if the parameter is variadic, false otherwise.
     * @throws ReflectionException If the method does not exist or reflection fails.
     *
     * @example
     * ```php
     * class Example {
     *     public function demo(string $name, ...$tags) {}
     * }
     *
     * $ref = new \oihana\reflect\Reflection();
     *
     * var_dump($ref->isParameterVariadic(Example::class, 'demo', 'name')); // bool(false)
     * var_dump($ref->isParameterVariadic(Example::class, 'demo', 'tags')); // bool(true)
     * ```
     */
    public function isParameterVariadic( object|string $class, string $method, string $param ): bool
    {
        $parameters = $this->parameters( $class , $method ) ;
        foreach ( $parameters as $p )
        {
            if ( $p->getName() === $param )
            {
                return $p->isVariadic();
            }
        }
        return false;
    }

    /**
     * Returns an array of methods for the given class or object.
     *
     * @param object|string $class The object or class name.
     * @param int $filter Method visibility filter (default: public).
     * @return array<int, ReflectionMethod> Array of reflection method objects.
     * @throws ReflectionException If reflection fails.
     *
     * @example
     * ```php
     * class MyClass
     * {
     *     public function foo() {}
     *     protected function bar() {}
     * }
     *
     * $methods = (new Reflection())->methods(MyClass::class);
     * foreach ($methods as $method)
     * {
     *     echo $method->getName(); // 'foo'
     * }
     * ```
     */
    public function methods( object|string $class, int $filter = ReflectionMethod::IS_PUBLIC ) : array
    {
        return $this->reflection( $class )->getMethods( $filter );
    }

    /**
     * Returns the default value of a parameter, if defined.
     *
     * @param object|string $class  The class name or object instance.
     * @param string        $method The method name.
     * @param string        $param  The parameter name to retrieve the default value for.
     *
     * @return mixed|null The default value of the parameter, or null if no default is defined.
     * @throws ReflectionException If the method does not exist or reflection fails.
     *
     * @example
     * ```php
     * class Example {
     *     public function testMethod(string $name, int $age = 30, $misc = null) {}
     * }
     *
     * $ref = new \oihana\reflect\Reflection();
     *
     * var_dump($ref->parameterDefaultValue(Example::class, 'testMethod', 'name')); // null (no default)
     * var_dump($ref->parameterDefaultValue(Example::class, 'testMethod', 'age'));  // int(30)
     * var_dump($ref->parameterDefaultValue(Example::class, 'testMethod', 'misc')); // null (explicit default)
     * ```
     */
    public function parameterDefaultValue( object|string $class, string $method, string $param ): mixed
    {
        $parameters = $this->parameters( $class , $method ) ;
        foreach ( $parameters as $p )
        {
            if ( $p->getName() === $param && $p->isDefaultValueAvailable() )
            {
                return $p->getDefaultValue();
            }
        }
        return null;
    }

    /**
     * Returns an array of parameters for a given method of a class.
     *
     * @param object|string $class  The class name or object.
     * @param string        $method The method name.
     *
     * @return ReflectionParameter[] An array of ReflectionParameter instances.
     * @throws ReflectionException If the method does not exist or cannot be reflected.
     *
     * @example
     * ```php
     * $params = (new Reflection())->parameters(MyClass::class, 'myMethod');
     * foreach ($params as $param)
     * {
     *     echo $param->getName(); // e.g. 'input'
     * }
     * ```
     */
    public function parameters( object|string $class, string $method ): array
    {
        $reflection = $this->reflection( $class ) ;

        if ( !$reflection->hasMethod( $method ) )
        {
            throw new ReflectionException("Method $method does not exist in class $class.");
        }

        return $reflection->getMethod( $method )->getParameters() ;
    }

    /**
     * Returns the type name of a specific parameter of a method, if declared.
     *
     * @param object|string $class The class name or an object instance.
     * @param string $method The method name.
     * @param string $param The parameter name to get the type for.
     *
     * @return string|null Type name as string or null if the parameter is not typed.
     * @throws ReflectionException If the method does not exist or reflection fails.
     *
     * @example
     * ```php
     * class Example
     * {
     *     public function testMethod(string $name, int $age = 30, $misc) {}
     * }
     *
     * $ref = new \oihana\reflect\Reflection();
     *
     * echo $ref->parameterType(Example::class, 'testMethod', 'name'); // outputs: string
     * echo $ref->parameterType(Example::class, 'testMethod', 'age');  // outputs: int
     * var_dump($ref->parameterType(Example::class, 'testMethod', 'misc')); // outputs: null
     * ```
     */
    public function parameterType( object|string $class, string $method, string $param ): ?string
    {
        $parameters = $this->parameters( $class , $method ) ;
        foreach ( $parameters as $p )
        {
            if ( $p->getName() === $param && $p->hasType() )
            {
                return $p->getType()->getName() ;
            }
        }
        return null ;
    }

    /**
     * Returns an array of properties for the given class or object.
     *
     * @param object|string $class The object or class name.
     * @param int $filter Property visibility filter (default: public).
     * @return ReflectionProperty[] An array of reflection property objects.
     * @throws ReflectionException If reflection fails.
     *
     * @example
     * ```php
     * class Item {
     *     public string $name;
     *     private int $id;
     * }
     * $props = (new Reflection())->properties(Item::class);
     * foreach ($props as $prop) {
     *     echo $prop->getName(); // 'name'
     * }
     * ```
     */
    public function properties( object|string $class , int $filter = ReflectionProperty::IS_PUBLIC ): array
    {
        return $this->reflection( $class )->getProperties( $filter ) ;
    }

    /**
     * Returns a cached ReflectionClass instance for the given class or object.
     *
     * @param object|string $class The object or class name.
     * @return ReflectionClass The reflection class.
     * @throws ReflectionException If reflection fails.
     *
     * @example
     * ```php
     * $reflectionClass = (new Reflection())->reflection(\App\Entity\User::class);
     * echo $reflectionClass->getName(); // 'App\Entity\User'
     * ```
     */
    public function reflection( object|string $class ): ReflectionClass
    {
        $className = is_string( $class ) ? $class : $class::class;

        if ( !isset( $this->reflections[ $className ] ) )
        {
            $this->reflections[ $className ] = new ReflectionClass( $className );
        }

        return $this->reflections[ $className ];
    }

    /**
     * Returns the short (unqualified) name of the class.
     *
     * @param object|string $class The object or class name.
     * @return string The short name of the class.
     * @throws ReflectionException If reflection fails.
     *
     * @example
     * ```php
     * echo (new Reflection())->shortName(\App\Models\Product::class);
     * // Output: 'Product'
     * ```
     */
    public function shortName( object|string $class ): string
    {
        return $this->reflection( $class )->getShortName() ;
    }

    /**
     * Internal cache of reflection instances.
     * @var array<string, ReflectionClass>
     */
    protected array $reflections = [] ;

    /**
     * Determine the type of an array element
     * @param $item
     * @param array $possibleClasses
     * @return string|null
     * @throws ReflectionException
     */
    private function determineArrayItemType($item, array $possibleClasses): ?string
    {
        if (!is_array($item))
        {
            return null ;
        }

        // Strategy 1: Search for a discriminator (field ‘type’, ‘@type’, etc.)
        if ( isset( $item[ '@type' ] ) )
        {
            $type = $item['@type'] ;
            foreach ( $possibleClasses as $class )
            {
                if ( $this->shortName( $class ) === $type || $class === $type )
                {
                    return $class ;
                }
            }
        }

        if ( isset($item['type'] ) )
        {
            $type = $item['type'];
            foreach ($possibleClasses as $class)
            {
                if ( strcasecmp( $this->shortName( $class ) , $type ) === 0 )
                {
                    return $class;
                }
            }
        }

        // Strategy 2: Analyze properties to guess the type
        return $this->guessClassFromProperties($item, $possibleClasses);
    }

    /**
     * Attempts to guess the most appropriate class from a list of possible classes
     * based on the presence of matching properties in the provided input array.
     *
     * The score is computed by checking if each class property (or its alternative
     * key defined by a `HydrateKey` attribute) exists in the `$item` array. The class
     * with the highest normalized score (above 0.3) is returned.
     *
     * If no class scores high enough, the first class in the `$possibleClasses` list is
     * returned as fallback (if provided), otherwise `null`.
     *
     * @param array $item             The associative array of input data to match against class properties.
     * @param array $possibleClasses  A list of fully qualified class names to consider.
     *
     * @return string|null            The best matching class name or `null` if none found.
     *
     * @throws ReflectionException   If a class cannot be reflected upon.
     *
     * @example
     * ```php
     * class User
     * {
     *     #[HydrateKey('user_id')]
     *     public string $id;
     *     public string $name;
     * }
     *
     * class Product
     * {
     *     public string $sku;
     *     public string $name;
     * }
     *
     * $item = ['user_id' => '123', 'name' => 'Alice'];
     * $guessedClass = $this->guessClassFromProperties($item, [User::class, Product::class]);
     *
     * echo $guessedClass; // Outputs: "User"
     * ```
     */
    private function guessClassFromProperties( array $item , array $possibleClasses ): ?string
    {
        $maxScore  = 0;
        $bestMatch = null;

        foreach ( $possibleClasses as $class )
        {
            if ( !class_exists( $class ) )
            {
                continue ;
            }

            $score      = 0 ;
            $properties = $this->properties( $class );
            $totalProps = count( $properties ) ;

            foreach ( $properties as $property )
            {
                $propertyName = $property->getName();

                if ( array_key_exists( $propertyName , $item ) )
                {
                    $score += 2 ; // Bonus for existing property
                }

                // Vérifier les attributs HydrateKey
                $keyAttr = $property->getAttributes(HydrateKey::class ) ;
                if ( !empty( $keyAttr ) )
                {
                    $alternativeKey = $keyAttr[0]->newInstance()->key ;
                    if ( array_key_exists( $alternativeKey , $item ) )
                    {
                        $score += 2 ;
                    }
                }
            }

            // Calculate normalized score
            $normalizedScore = $totalProps > 0 ? ($score / ($totalProps * 2)) : 0 ;

            if ($normalizedScore > $maxScore)
            {
                $maxScore = $normalizedScore;
                $bestMatch = $class;
            }
        }

        // Return the best match if the score is sufficient
        return $maxScore > 0.3 ? $bestMatch : $possibleClasses[0] ?? null;
    }
}