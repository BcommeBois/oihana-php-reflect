# Reflection API

[← Back to index](README.md)

`oihana\reflect\Reflection` is a high-level, cached wrapper around PHP's Reflection API. `oihana\reflect\traits\ReflectionTrait` exposes the same helpers (plus `toArray()`) on your own classes.

```php
use oihana\reflect\Reflection;

$reflection = new Reflection(); // caches ReflectionClass instances and hydration plans
```

## Classes, constants, methods, properties

```php
$reflection->constants( MyClass::class );                 // ['FOO' => 'bar', ...] (public by default)
$reflection->methods( MyClass::class );                   // ReflectionMethod[] (public by default)
$reflection->properties( MyClass::class );                // ReflectionProperty[] (public by default)
$reflection->reflection( MyClass::class );                // the cached ReflectionClass
```

Visibility filters use the native bitmasks, e.g. `$reflection->properties( $c , ReflectionProperty::IS_PROTECTED )`.

## Class / property introspection

```php
$reflection->hasMethod( MyClass::class , 'doThing' );     // bool (never throws)
$reflection->hasProperty( MyClass::class , 'name' );      // bool
$reflection->propertyType( MyClass::class , 'name' );     // 'string' | 'int|string' | null
$reflection->namespace( MyClass::class );                 // 'App\Model' ('' if global)
$reflection->shortName( MyClass::class );                 // 'MyClass'
```

`propertyType()` renders union types as `A|B` and intersection types as `A&B`; it returns `null` for an untyped or missing property.

## Method parameters

```php
$reflection->parameters( MyClass::class , 'demo' );                 // ReflectionParameter[]
$reflection->hasParameter( MyClass::class , 'demo' , 'name' );      // bool
$reflection->parameterType( MyClass::class , 'demo' , 'name' );     // 'string' | 'int|string' | null
$reflection->parameterDefaultValue( MyClass::class , 'demo' , 'x' );// mixed|null
$reflection->isParameterNullable( MyClass::class , 'demo' , 'x' );  // bool
$reflection->isParameterOptional( MyClass::class , 'demo' , 'x' );  // bool
$reflection->isParameterVariadic( MyClass::class , 'demo' , 'x' );  // bool
```

## Describing any callable

`describeCallableParameters()` works on closures, function names, `Class::method` strings, `[ $obj, 'method' ]` arrays and invokable objects:

```php
$reflection->describeCallableParameters( fn( string $name , int $age = 42 ) => '' );
// [
//   [ 'name' => 'name', 'type' => 'string', 'optional' => false, 'nullable' => false, 'variadic' => false ],
//   [ 'name' => 'age',  'type' => 'int',    'optional' => true,  'nullable' => false, 'variadic' => false, 'default' => 42 ],
// ]
```

The descriptor keys are named constants in `oihana\reflect\enums\CallableParameter` (`NAME`, `TYPE`, `OPTIONAL`, `NULLABLE`, `VARIADIC`, `DEFAULT`).

## Hydration

`hydrate()` is documented in its own section — see [Hydration](hydration/README.md).

```php
$reflection->hydrate( $data , MyClass::class );
```

## `ReflectionTrait`

Add the trait to expose the helpers on instances, plus `toArray()`:

```php
use oihana\reflect\traits\ReflectionTrait;

class User
{
    use ReflectionTrait;
    public string  $name = '';
    public ?string $bio  = null;
    public int     $age  = 0;
}
```

Wrappers: `getConstants()`, `getPublicProperties()`, `getShortName()`, `getNamespace()`, `getMethodParameters()`, `getParameterType()`, `getParameterDefaultValue()`, `hasParameter()`, `hasMethod()`, `hasProperty()`, `getPropertyType()`, `isParameterNullable/Optional/Variadic()`, and `hydrate()`.

### `toArray()`

Serializes the **public, initialized** properties to an array, with options from `oihana\core\options\ArrayOption`:

```php
use oihana\core\options\ArrayOption;

new User()->toArray(
[
    ArrayOption::REDUCE     => true,                  // drop null values
    ArrayOption::INCLUDE    => [ 'name', 'age' ],     // whitelist
    ArrayOption::EXCLUDE    => [ 'bio' ],             // blacklist
    ArrayOption::BEFORE     => [ '_type' => 'User' ], // prepend
    ArrayOption::AFTER      => [],                     // append
    ArrayOption::FIRST_KEYS => [ '_type', 'name' ],   // force order
    ArrayOption::SORT       => true,                   // sort remaining keys
    ArrayOption::DEFAULTS   => [ 'bio' => 'n/a' ],     // defaults for missing/null
] );
```

Properties marked [`#[Transient]` / `#[HydrateIgnore]`](hydration/attributes.md#transient--hydrateignore) are never emitted.

## See also

- [Hydration](hydration/README.md) · [JSON Schema](json-schema.md) · [Serialization](serialization.md)
