# Helpers

[← Back to index](README.md)

Standalone functions in the `oihana\reflect\helpers` namespace, autoloaded via Composer `files`.

## getFunctionInfo

```php
use function oihana\reflect\helpers\getFunctionInfo;

$info = getFunctionInfo( 'strlen' );
// or a closure, a 'Class::method' string, or [ $obj, 'method' ]
```

Returns an associative array describing a callable — `name`, `namespace`, `alias` (short name), `startLine`, and more — or `null` when it does not exist.

## getPublicProperties

```php
use function oihana\reflect\helpers\getPublicProperties;

$props = getPublicProperties( new ReflectionClass( User::class ) );
```

Returns all **public, non-static** properties of a class, including those inherited from traits and parent classes.

- `getPublicProperties( ReflectionClass $class , bool $recursive = true , array &$cache = [] ) : array`
- Pass an external `&$cache` array to memoize results across calls.

## hasTrait

```php
use function oihana\reflect\helpers\hasTrait;

hasTrait( new ReflectionClass( User::class ) , ReflectionTrait::class ); // bool
```

Checks whether a class uses a given trait — including traits pulled in by parent classes and nested traits.

- `hasTrait( ReflectionClass $class , string $traitName , bool $recursive = true , array &$cache = [] ) : bool`

## useConstantsTrait

```php
use function oihana\reflect\helpers\useConstantsTrait;

useConstantsTrait( Color::class ); // bool — does Color use ConstantsTrait?
```

A focused check for the [`ConstantsTrait`](constants-trait.md), either directly or via parent classes.

- `useConstantsTrait( string $className , array &$cache = [] ) : bool`
