<?php

namespace oihana\reflect;

use BackedEnum;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

use oihana\reflect\enums\PhpType;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

use oihana\reflect\attributes\HydrateAs;
use oihana\reflect\attributes\HydrateKey;
use oihana\reflect\attributes\HydrateWith;
use oihana\reflect\enums\CallableParameter;
use oihana\reflect\enums\HydrateDiscriminator;
use oihana\reflect\enums\HydrationPlan;

use function oihana\core\arrays\isAssociative;
use function oihana\core\callables\resolveCallable;

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
 * - PHPDoc support for array element types via `@var `XXX`[]` and `@var array<Type>`
 * - Backed enums: scalar values are resolved to enum cases via `Enum::from()`
 *   (single values and arrays of enums via {@see HydrateWith} or `@var Enum[]`).
 *   Pure (non-backed) enums have no scalar representation: hydrating one from a scalar
 *   throws an `InvalidArgumentException` (declare a backed enum instead)
 * - DateTimeInterface: scalar values are resolved to date instances (string → parsed date,
 *   int → Unix timestamp). A builtin scalar member of a union (e.g. `string|DateTimeInterface`)
 *   keeps the raw value unless {@see HydrateAs} explicitly forces the date class.
 * - Assigns public properties only (private/protected are ignored by design); values are
 *   set via reflection, so `readonly` and asymmetric-visibility properties
 *   (`public private(set)`/`public protected(set)`) are supported
 * - Scalar coercion: values are converted to the declared scalar type following PHP's
 *   coercive typing (e.g. `'42'` → `int 42`, `7` → `string '7'`); a value that cannot be
 *   coerced to the declared type raises a `TypeError` (independent of `strict_types`)
 *
 * Caching:
 * - Internally caches {@see ReflectionClass} instances per fully-qualified class name for better performance
 * - Caches a per-class **hydration plan** (attributes, `@var` item types, constructor strategy,
 *   builtin types) so the data-independent reflection work is computed once and reused for every
 *   object of that class. The cache is in-memory, bounded by the number of hydrated classes, and
 *   needs no eviction (it lives for the process). This roughly cuts hydration time by a third on
 *   nested documents (the deeper the nesting, the larger the gain).
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
        $resolved = resolveCallable( $callable );

        if ( $resolved === null )
        {
            throw new InvalidArgumentException( 'Cannot resolve callable.' );
        }

        $reflection = match( true )
        {
            is_array( $resolved )                                             => new ReflectionMethod( $resolved[0], $resolved[1] ) ,
            is_object( $resolved ) && ! $resolved instanceof Closure          => new ReflectionMethod( $resolved, '__invoke' ) ,
            is_string( $resolved ) && str_contains( $resolved , '::' ) => new ReflectionMethod( ...explode( '::', $resolved, 2 ) ) ,
            default                                                           => new ReflectionFunction( $resolved ), // Closure or plain function name
        };

        $result = [];

        foreach ( $reflection->getParameters() as $p )
        {
            $type      = $p->getType() ;
            $typeName  = null ;
            $nullable  = false ;

            if ( $type instanceof ReflectionUnionType )
            {
                $types = [];
                foreach ( $type->getTypes() as $t )
                {
                    $types[] = $t->getName();
                    if ( $t->getName() === PhpType::NULL )
                    {
                        $nullable = true ;
                    }
                }
                $typeName = implode('|', $types) ;
            }
            elseif ( $type instanceof ReflectionNamedType )
            {
                $typeName = $type->getName() ;
                $nullable = $type->allowsNull() ;
            }

            $paramData =
            [
                CallableParameter::NAME     => $p->getName()    ,
                CallableParameter::TYPE     => $typeName        ,
                CallableParameter::OPTIONAL => $p->isOptional() ,
                CallableParameter::NULLABLE => $nullable        ,
                CallableParameter::VARIADIC => $p->isVariadic() ,
            ];

            if ( $p->isDefaultValueAvailable() )
            {
                $paramData[ CallableParameter::DEFAULT ] = $p->getDefaultValue() ;
            }

            $result[] = $paramData ;
        }

        return $result ;
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
     * Checks whether the given class (or object) declares the given method.
     *
     * Unlike {@see self::parameters()}, this never throws when the method is missing.
     *
     * @param object|string $class  The object or class name.
     * @param string        $method The method name.
     *
     * @return bool True if the method exists, false otherwise.
     * @throws ReflectionException If the class cannot be reflected.
     *
     * @example
     * ```php
     * new Reflection()->hasMethod(MyClass::class, 'doThing'); // true|false
     * ```
     */
    public function hasMethod( object|string $class, string $method ): bool
    {
        return $this->reflection( $class )->hasMethod( $method );
    }

    /**
     * Checks whether the given class (or object) declares the given property.
     *
     * @param object|string $class    The object or class name.
     * @param string        $property The property name.
     *
     * @return bool True if the property exists, false otherwise.
     * @throws ReflectionException If the class cannot be reflected.
     *
     * @example
     * ```php
     * new Reflection()->hasProperty(User::class, 'name'); // true|false
     * ```
     */
    public function hasProperty( object|string $class, string $property ): bool
    {
        return $this->reflection( $class )->hasProperty( $property );
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
     * Instantiation: a constructor that is callable with no arguments is invoked normally
     * (preserving its side effects). When the constructor declares required arguments, the
     * object is created via `newInstanceWithoutConstructor()` and populated from the data;
     * declared property defaults still apply, but a required property absent from the data
     * stays uninitialized (accessing it later raises the usual "must not be accessed before
     * initialization" error).
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

        // Per-class hydration plan : the data-independent reflection work (attributes,
        // @var docblock parsing, constructor analysis, builtin types) is computed once
        // and reused for every object of this class. See buildHydrationPlan().
        $plan = $this->plans[ $class ] ??= $this->buildHydrationPlan( $class ) ;

        // Bypass the constructor only when it declares required arguments (which `new $class()`
        // could not satisfy). A constructor callable with no arguments still runs normally.
        $object = $plan[ HydrationPlan::BYPASS_CONSTRUCTOR ]
                ? $this->reflection( $class )->newInstanceWithoutConstructor()
                : new $class() ;

        foreach ( $plan[ HydrationPlan::PROPERTIES ] as $meta )
        {
            $propertyKey = $meta[ HydrationPlan::KEY ] ;

            if ( !array_key_exists( $propertyKey , $thing ) )
            {
                continue;
            }

            $value    = $thing[ $propertyKey ] ;
            $property = $meta[ HydrationPlan::PROPERTY ] ;

            if ( $meta[ HydrationPlan::HAS_TYPE ] )
            {
                $types            = $meta[ HydrationPlan::TYPES ] ;
                $builtinTypeNames = $meta[ HydrationPlan::BUILTINS ] ;
                $hydrateAs        = $meta[ HydrationPlan::AS ] ;      // class-string|null
                $hydrateWith      = $meta[ HydrationPlan::WITH ] ;    // class-string[]|null
                $docItemClass     = $meta[ HydrationPlan::DOC_ITEM ] ; // class-string|null
                $hydrated         = false ;

                // Try the `array` type first when #[HydrateWith] applies to an array value.
                if ( $hydrateWith !== null && is_array( $value ) )
                {
                    $arrayType  = null;
                    $otherTypes = [];

                    foreach ( $types as $type )
                    {
                        if ( $type->getName() === PhpType::ARRAY )
                        {
                            $arrayType = $type;
                        }
                        else
                        {
                            $otherTypes[] = $type;
                        }
                    }

                    if ( $arrayType !== null )
                    {
                        $types = array_merge( [$arrayType], $otherTypes );
                    }
                }

                foreach ( $types as $type )
                {
                    $typeName = $type->getName();

                    if ( $typeName === PhpType::NULL && $value === null )
                    {
                        break ;
                    }

                    // Attribut #[HydrateAs(Foo::class)]
                    if ( $hydrateAs !== null )
                    {
                        $typeName = $hydrateAs ;
                    }

                    if ( $hydrateWith !== null && is_array( $value ) && isAssociative( $value ) )
                    {
                        $itemClass = $this->determineArrayItemType( $value , $hydrateWith );
                        if ( $itemClass && class_exists( $itemClass ) )
                        {
                            $value = $this->hydrate( $value, $itemClass );
                            $hydrated = true ;
                            break ;
                        }
                    }

                    // Backed enum : scalar value -> enum instance (e.g. 'active' -> Status::Active)
                    if ( enum_exists( $typeName ) )
                    {
                        $value = $this->castEnum( $typeName , $value ) ;
                        if ( $value instanceof $typeName )
                        {
                            $hydrated = true ;
                            break ;
                        }
                        // null / non-scalar / pure enum : fall through to default handling
                    }

                    // DateTimeInterface : scalar value -> date instance.
                    // A builtin scalar member of the union (string/int/mixed) keeps the raw value,
                    // unless an explicit #[HydrateAs] forces the date class.
                    if ( is_a( $typeName , DateTimeInterface::class , true ) )
                    {
                        $valueKind = match( true )
                        {
                            is_string( $value ) => PhpType::STRING  ,
                            is_int( $value )    => PhpType::INTEGER ,
                            is_float( $value )  => PhpType::FLOAT   ,
                            is_bool( $value )   => PhpType::BOOLEAN ,
                            default             => null     ,
                        } ;

                        $scalarWins = $hydrateAs === null
                                   && ( in_array( PhpType::MIXED , $builtinTypeNames , true )
                                     || ( $valueKind !== null && in_array( $valueKind , $builtinTypeNames , true ) ) ) ;

                        if ( !$scalarWins )
                        {
                            $value = $this->castDate( $typeName , $value ) ;
                            if ( $value instanceof DateTimeInterface )
                            {
                                $hydrated = true ;
                                break ;
                            }
                        }
                        // scalar wins / null / non-scalar : fall through to default handling
                    }

                    if ( $typeName === PhpType::ARRAY && is_array( $value ) )
                    {
                        // 1. #[HydrateWith(MyClass::class, AnotherClass::class)]
                        if ( $hydrateWith !== null )
                        {
                            $hydratedArray = [] ;
                            foreach ( $value as $item )
                            {
                                if ( is_array( $item ) )
                                {
                                    $itemClass = $this->determineArrayItemType( $item , $hydrateWith ) ;
                                    if ( $itemClass && class_exists( $itemClass ) )
                                    {
                                        $hydratedArray[] = $this->hydrate( $item , $itemClass ) ;
                                    }
                                    else
                                    {
                                        $hydratedArray[] = $item ; // Do nothing
                                    }
                                }
                                else if ( count( $hydrateWith ) === 1 && enum_exists( $hydrateWith[0] ) )
                                {
                                    $hydratedArray[] = $this->castEnum( $hydrateWith[0] , $item ) ;
                                }
                                else
                                {
                                    $hydratedArray[] = $item ;
                                }
                            }
                            $value    = $hydratedArray;
                            $hydrated = true ;
                            break ;
                        }

                        // 2. PHPDoc @var Type[] / @var array<Type> (item class resolved once in the plan)
                        if ( $docItemClass !== null )
                        {
                            $isEnum   = enum_exists( $docItemClass ) ;
                            $isDate   = is_a( $docItemClass , DateTimeInterface::class , true ) ;
                            $value    = array_map
                            (
                                fn( $v ) => match( true )
                                {
                                    is_array( $v ) => $this->hydrate( $v , $docItemClass ) ,
                                    $isEnum        => $this->castEnum( $docItemClass , $v ) ,
                                    $isDate        => $this->castDate( $docItemClass , $v ) ,
                                    default        => $v ,
                                } ,
                                $value
                            );
                            $hydrated = true ;
                            break;
                        }
                    }
                    else if ( class_exists( $typeName ) )
                    {
                        if ( is_array( $value ) )
                        {
                            $value = isAssociative( $value )
                                   ? $this->hydrate( $value , $typeName )
                                   : array_map( fn($v) => is_array($v) ? $this->hydrate( $v , $typeName ) : $v , $value ) ;
                            $hydrated = true ;
                            break;
                        }
                    }
                }

                if ( !$hydrated && $value === null && !$meta[ HydrationPlan::ALLOWS_NULL ] )
                {
                    throw new InvalidArgumentException("Property {$property->getName()} does not allow null" ) ;
                }
            }

            if ( $meta[ HydrationPlan::IS_PUBLIC ] )
            {
                // setValue() (instead of a direct assignment) so readonly and
                // asymmetric-visibility properties (public private(set)/protected(set))
                // can be initialized; scalar type coercion is preserved.
                $property->setValue( $object , $value ) ;
            }
        }

        return $object ;
    }

    /**
     * Builds the data-independent hydration plan for a class.
     *
     * The plan caches everything that only depends on the class definition (and never on
     * the data being hydrated): the constructor strategy and, per property, its source key
     * ({@see HydrateKey}), declared types, builtin type names, resolved {@see HydrateAs} /
     * {@see HydrateWith} classes, the PHPDoc `
     *
     * @param class-string $class The fully-qualified class name.
     *
     * @return array{bypassConstructor: bool, properties: array<int, array<string, mixed>>}
     *
     * @throws ReflectionException If reflection fails.
     */
    private function buildHydrationPlan( string $class ) : array
    {
        $reflectionClass = $this->reflection( $class ) ;

        $constructor       = $reflectionClass->getConstructor() ;
        $bypassConstructor = $constructor !== null && $constructor->getNumberOfRequiredParameters() > 0 ;

        $properties = [] ;

        foreach ( $reflectionClass->getProperties() as $property )
        {
            $key     = $property->getName() ;
            $keyAttr = $property->getAttributes( HydrateKey::class ) ;
            if ( !empty( $keyAttr ) )
            {
                $key = $keyAttr[0]->newInstance()->key ;
            }

            $hasType    = $property->hasType() ;
            $types      = [] ;
            $builtins   = [] ;
            $allowsNull = false ;
            $docItem    = null ;

            if ( $hasType )
            {
                $propertyType = $property->getType() ;
                $allowsNull   = $propertyType->allowsNull() ;
                $types        = $propertyType instanceof ReflectionUnionType
                              ? $propertyType->getTypes()
                              : [ $propertyType ] ;

                foreach ( $types as $t )
                {
                    if ( $t->isBuiltin() )
                    {
                        $builtins[] = $t->getName() ;
                    }
                }

                $doc = $property->getDocComment() ;
                if ( $doc && preg_match( '/@var\s+(?:([\w\\\\]+)\[]|array<([\w\\\\]+)>)/' , $doc , $matches ) )
                {
                    $candidate = ( $matches[1] ?? '' ) ?: ( $matches[2] ?? '' ) ;
                    if ( class_exists( $candidate ) )
                    {
                        $docItem = $candidate ;
                    }
                }
            }

            $asAttr   = $property->getAttributes( HydrateAs::class   ) ;
            $withAttr = $property->getAttributes( HydrateWith::class ) ;

            $properties[] =
            [
                HydrationPlan::PROPERTY    => $property ,
                HydrationPlan::KEY         => $key ,
                HydrationPlan::HAS_TYPE    => $hasType ,
                HydrationPlan::TYPES       => $types ,
                HydrationPlan::BUILTINS    => $builtins ,
                HydrationPlan::ALLOWS_NULL => $allowsNull ,
                HydrationPlan::AS          => empty( $asAttr )   ? null : $asAttr[0]->newInstance()->class ,
                HydrationPlan::WITH        => empty( $withAttr ) ? null : $withAttr[0]->newInstance()->classes ,
                HydrationPlan::DOC_ITEM    => $docItem ,
                HydrationPlan::IS_PUBLIC   => $property->isPublic() ,
            ] ;
        }

        return [ HydrationPlan::BYPASS_CONSTRUCTOR => $bypassConstructor , HydrationPlan::PROPERTIES => $properties ] ;
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
                return $this->typeToString( $p->getType() ) ;
            }
        }
        return null ;
    }

    /**
     * Returns the declared type of a property as a string, or null when untyped/absent.
     *
     * Union types are rendered as `A|B` and intersection types as `A&B` (consistent with
     * {@see self::describeCallableParameters()} and {@see self::parameterType()}).
     *
     * @param object|string $class    The object or class name.
     * @param string        $property The property name.
     *
     * @return string|null The type name (e.g. `int`, `?string`→`string`, `int|string`), or null.
     * @throws ReflectionException If the class cannot be reflected.
     *
     * @example
     * ```php
     * class User { public int $age; public int|string $id; public $misc; }
     *
     * $ref = new \oihana\reflect\Reflection();
     * echo $ref->propertyType(User::class, 'age'); // 'int'
     * echo $ref->propertyType(User::class, 'id');  // 'int|string'
     * var_dump($ref->propertyType(User::class, 'misc')); // null (untyped)
     * ```
     */
    public function propertyType( object|string $class, string $property ): ?string
    {
        $reflection = $this->reflection( $class ) ;

        if ( !$reflection->hasProperty( $property ) )
        {
            return null ;
        }

        return $this->typeToString( $reflection->getProperty( $property )->getType() ) ;
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
     * foreach ($props as $prop)
     * {
     *     echo $prop->getName(); // 'name'
     * }
     * ```
     */
    public function properties
    (
        object|string $class ,
        int $filter = ReflectionProperty::IS_PUBLIC
    )
    : array
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
     * Returns the namespace of the class (empty string for the global namespace).
     *
     * @param object|string $class The object or class name.
     * @return string The namespace name, or '' when the class is in the global namespace.
     * @throws ReflectionException If reflection fails.
     *
     * @example
     * ```php
     * echo new Reflection()->namespace(\App\Models\Product::class);
     * // Output: 'App\Models'
     * ```
     */
    public function namespace( object|string $class ): string
    {
        return $this->reflection( $class )->getNamespaceName() ;
    }

    /**
     * Renders a {@see ReflectionType} as a string.
     *
     * Named types return their name, union types are joined with `|`, intersection types
     * with `&`, and a null/unsupported type returns null.
     *
     * @param ReflectionType|null $type The type to render.
     * @return string|null The rendered type name, or null when untyped.
     */
    private function typeToString( ?ReflectionType $type ): ?string
    {
        return match( true )
        {
            $type instanceof ReflectionNamedType        => $type->getName() ,
            $type instanceof ReflectionUnionType        => implode( '|' , array_map( fn( $t ) => $this->typeToString( $t ) , $type->getTypes() ) ) ,
            $type instanceof ReflectionIntersectionType => implode( '&' , array_map( fn( $t ) => $this->typeToString( $t ) , $type->getTypes() ) ) ,
            default                                     => null ,
        } ;
    }

    /**
     * Internal cache of reflection instances.
     * @var array<string, ReflectionClass>
     */
    protected array $reflections = [] ;

    /**
     * Internal cache of per-class hydration plans (see {@see self::buildHydrationPlan()}).
     * Keyed by fully-qualified class name; bounded by the number of hydrated classes.
     * @var array<string, array{bypassConstructor: bool, properties: array<int, array<string, mixed>>}>
     */
    protected array $plans = [] ;

    /**
     * Converts a scalar value into a backed-enum instance when possible.
     *
     * Resolution rules:
     * - If `$value` is already an instance of `$enumClass`, it is returned unchanged.
     * - If `$enumClass` is a {@see BackedEnum} and `$value` is an int or a string,
     *   the matching case is resolved via `$enumClass::from()` (throws {@see \ValueError}
     *   on an unknown value, by design — invalid input must fail loudly).
     * - If `$enumClass` is a pure (non-backed) enum and `$value` is a scalar, an
     *   {@see InvalidArgumentException} is thrown: a pure enum has no scalar representation,
     *   so it cannot be hydrated from data — declare it as a backed enum instead.
     * - Otherwise (null, non-scalar value) the original `$value` is returned unchanged so
     *   the caller can apply its default handling.
     *
     * @param class-string $enumClass The fully-qualified enum class name.
     * @param mixed        $value     The incoming value to convert.
     *
     * @return mixed The enum instance, or the original value when no conversion applies.
     *
     * @throws InvalidArgumentException If a pure (non-backed) enum is hydrated from a scalar.
     *
     * @example
     * ```php
     * enum Status: string { case Active = 'active'; }
     * $this->castEnum( Status::class , 'active' ); // Status::Active
     * $this->castEnum( Status::class , Status::Active ); // Status::Active (unchanged)
     * ```
     */
    private function castEnum( string $enumClass , mixed $value ) : mixed
    {
        if ( $value instanceof $enumClass )
        {
            return $value ;
        }

        if ( is_int( $value ) || is_string( $value ) )
        {
            if ( is_subclass_of( $enumClass , BackedEnum::class ) )
            {
                return $enumClass::from( $value ) ;
            }

            // Pure (non-backed) enum: no scalar representation -> fail loud with a clear message.
            throw new InvalidArgumentException
            (
                "Cannot hydrate the pure (non-backed) enum '$enumClass' from a scalar value; declare it as a backed enum instead."
            ) ;
        }

        return $value ;
    }

    /**
     * Converts a scalar value into a {@see DateTimeInterface} instance when possible.
     *
     * Resolution rules:
     * - If `$value` is already a {@see DateTimeInterface}, it is returned unchanged.
     * - The concrete class instantiated mirrors the declared type: `DateTime` stays
     *   mutable, `DateTimeImmutable` (and any subclass) stays immutable, and the abstract
     *   `DateTimeInterface` defaults to {@see DateTimeImmutable}.
     * - An `int` is interpreted as a Unix timestamp (seconds).
     * - A non-empty `string` is parsed as a date (ISO 8601 or any format understood by the
     *   date constructor) and throws on an unparsable value (fail loud, by design).
     * - Otherwise (null, empty string, ...) the original `$value` is returned unchanged so
     *   the caller can apply its default handling.
     *
     * Note: a numeric timestamp must be passed as an `int`; a numeric *string* is treated
     * as a date string, not a timestamp.
     *
     * @param class-string $dateClass The declared date class (DateTime, DateTimeImmutable, ... or DateTimeInterface).
     * @param mixed        $value     The incoming value to convert.
     *
     * @return mixed The date instance, or the original value when no conversion applies.
     *
     * @example
     * ```php
     * $this->castDate( DateTimeImmutable::class , '2024-01-01T00:00:00Z' ); // DateTimeImmutable
     * $this->castDate( DateTimeImmutable::class , 1704067200 );             // DateTimeImmutable (from timestamp)
     * ```
     */
    private function castDate( string $dateClass , mixed $value ) : mixed
    {
        if ( $value instanceof DateTimeInterface )
        {
            return $value ;
        }

        $target = $dateClass === DateTimeInterface::class ? DateTimeImmutable::class : $dateClass ;

        if ( is_int( $value ) )
        {
            return new $target()->setTimestamp( $value ) ;
        }

        if ( is_string( $value ) && $value !== '' )
        {
            return new $target( $value ) ;
        }

        return $value ;
    }

    /**
     * Determines the most appropriate class type for a given array item.
     *
     * This method is typically used when hydrating arrays of objects whose element type
     * is ambiguous. It attempts to infer the class of the element using two strategies:
     *
     *  1. **Discriminator field detection** — if the array contains a field such as
     *     `@type` or `type`, its value is matched (case-sensitive or case-insensitive)
     *     against the short name or the fully qualified name of one of the `$possibleClasses`.
     *
     *  2. **Property-based guessing** — if no discriminator is found or matched,
     *     the method calls `guessClassFromProperties()` to infer the most likely class
     *     based on the array's keys.
     *
     * @param  mixed $item
     *         The array element to analyze. If the value is not an array, `null` is returned.
     *
     * @param  array<class-string> $possibleClasses
     *         A list of fully qualified class names that represent the possible types
     *         for this array element.
     *
     * @return class-string|null
     *         The fully qualified class name inferred from the given `$item`, or `null`
     *         if no matching class could be determined.
     *
     * @throws ReflectionException
     *         If reflection operations fail when analyzing candidate classes.
     *
     * @see self::shortName()                For normalization of class names.
     * @see self::guessClassFromProperties() For heuristic detection based on keys.
     */
    private function determineArrayItemType( array $item , array $possibleClasses ) :?string
    {
        // Strategy 1: Search for a discriminator (field 'atType', ‘type’ or ‘@type)

        $discriminatorKeys = HydrateDiscriminator::keys() ;

        foreach ( $discriminatorKeys as $key )
        {
            if ( isset( $item[ $key ] ) )
            {
                $type = $item[ $key ];
                foreach ( $possibleClasses as $class )
                {
                    if // Case-insensitive comparison on both the short name AND the full name
                    (
                        strcasecmp( $this->shortName( $class ), $type ) === 0 ||
                        strcasecmp( $class , $type ) === 0
                    )
                    {
                        return $class ;
                    }
                }
            }
        }

        // Strategy 2: Analyze properties to guess the type

        return $this->guessClassFromProperties( $item , $possibleClasses ) ;
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
        if ( empty( $possibleClasses ) )
        {
            return null;
        }

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

                // Check HydrateKey attributes
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
        return $maxScore > 0.3 ? $bestMatch : $possibleClasses[0] ?? null ;
    }
}