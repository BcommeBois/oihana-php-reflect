# Getting started

[← Back to index](README.md)

## Installation

```bash
composer require oihana/php-reflect
```

Requirements:

- PHP **8.4+**
- `oihana/php-core` (installed automatically)

## Two entry points

The library is used in two complementary ways:

1. **Directly**, through the `Reflection` class — instantiate it once and reuse it (it caches `ReflectionClass` instances and hydration plans):

   ```php
   use oihana\reflect\Reflection;

   $reflection = new Reflection();
   ```

2. **As a trait**, through `ReflectionTrait` — add reflection/hydration/`toArray()` helpers to your own classes without instantiating anything:

   ```php
   use oihana\reflect\traits\ReflectionTrait;

   class User
   {
       use ReflectionTrait;
       public string $name = '';
   }
   ```

## Your first hydration

`hydrate()` turns an associative array into a typed object, recursively:

```php
use oihana\reflect\Reflection;

class Address { public string $city = ''; }

class User
{
    public string   $name = '';
    public ?Address $address = null;
}

$user = new Reflection()->hydrate(
[
    'name'    => 'Alice',
    'address' => [ 'city' => 'Paris' ],
] , User::class );

$user->name;          // 'Alice'
$user->address->city; // 'Paris'
```

The hydrator only assigns **public properties**, matches array keys to property names (or to aliases declared with [`#[HydrateKey]`](hydration/attributes.md)), and resolves nested objects, arrays of objects, enums and dates automatically. See the [Hydration overview](hydration/README.md).

## Your first reflection calls

```php
use oihana\reflect\Reflection;

$reflection = new Reflection();

$reflection->hasMethod( User::class , 'getName' );      // bool
$reflection->hasProperty( User::class , 'address' );    // true
$reflection->propertyType( User::class , 'address' );   // 'Address' (or null)
$reflection->namespace( User::class );                  // '' or the FQCN namespace
$reflection->shortName( User::class );                  // 'User'
```

See the [Reflection API](reflection.md) for the full surface.

## Exposing an object as an array

With `ReflectionTrait`, `toArray()` serializes the public, initialized properties — with options for filtering, ordering and null reduction:

```php
use oihana\core\options\ArrayOption;
use oihana\reflect\traits\ReflectionTrait;

class Product
{
    use ReflectionTrait;
    public string  $name = 'Book';
    public ?string $desc = null;
    public int     $stock = 0;
}

new Product()->toArray( [ ArrayOption::REDUCE => true ] );
// [ 'name' => 'Book', 'stock' => 0 ]  (null 'desc' removed)
```

## Where to go next

- [Hydration overview](hydration/README.md) — the heart of the library.
- [Attributes](hydration/attributes.md) — declarative mapping.
- [Reflection API](reflection.md) — introspection helpers.
