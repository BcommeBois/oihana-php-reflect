# oihana/php-reflect — Reflection & hydration for PHP

![Language](https://img.shields.io/badge/language-English-blue)

`oihana/php-reflect` is a small, focused PHP library that provides:

- a friendly wrapper around PHP's Reflection API (`Reflection`, `ReflectionTrait`);
- a robust **array-to-object hydrator** with attribute-based mapping, enum & date resolution, and a per-class plan cache;
- constants "enum-like" helpers (`ConstantsTrait`), a JSON Schema generator (`JsonSchemaTrait`), JSON/CBOR serializers, and a compact `Version` value object.

![Oihana PHP Reflect](https://raw.githubusercontent.com/BcommeBois/oihana-php-reflect/main/assets/images/oihana-php-reflect-logo-inline-512x160.png)

## Who this documentation is for

PHP developers who want to:

- turn associative arrays (JSON payloads, database rows) into **typed objects** — recursively, with enums, dates, unions, `readonly` properties and required constructors handled for you;
- map external key names and polymorphic collections declaratively, via attributes, with **zero magic strings**;
- introspect classes, methods, properties and callables through a concise, cached API;
- expose public properties as arrays / JSON, validate data against a generated JSON Schema, or work with class constants as enumerations.

## Quick start

```php
use oihana\reflect\Reflection;
use oihana\reflect\attributes\HydrateKey;

enum Status : string { case Active = 'active'; case Inactive = 'inactive'; }

class User
{
    #[HydrateKey( 'user_name' )]
    public string $name = '';
    public Status $status = Status::Inactive;
    public ?DateTimeImmutable $createdAt = null;
}

$user = new Reflection()->hydrate(
[
    'user_name' => 'Alice',
    'status'    => 'active',
    'createdAt' => '2024-01-02T03:04:05+00:00',
] , User::class );

$user->name;             // 'Alice'
$user->status;           // Status::Active
$user->createdAt;        // DateTimeImmutable
```

> PHP 8.4 lets you call a method directly on a `new` expression — `new Reflection()->hydrate(...)` — without wrapping parentheses. All examples in this wiki use that syntax.

## Table of contents

### Getting started
- [Getting started](getting-started.md) — installation, requirements, your first hydration and reflection calls.

### Hydration (the core)
- [Overview](hydration/README.md) — what the hydrator does and how it resolves values.
- [Attributes](hydration/attributes.md) — `#[HydrateKey]`, `#[HydrateWith]`, `#[HydrateAs]`, `#[Transient]` / `#[HydrateIgnore]`.
- [Types](hydration/types.md) — enums, dates, unions & nullability, scalar coercion, `readonly` & required constructors.
- [Errors](hydration/errors.md) — `HydrationException` and how to handle invalid records.
- [Performance](hydration/performance.md) — the per-class hydration plan cache.

### Reflection & utilities
- [Reflection API](reflection.md) — constants, methods, properties, parameters and class/property introspection.
- [ConstantsTrait](constants-trait.md) — treat class constants as enumerations.
- [FunctionCallTrait](function-call-trait.md) — parse and manipulate function-call expressions.
- [JSON Schema](json-schema.md) — generate and validate a JSON Schema from a class.
- [Serialization](serialization.md) — `JsonSerializer`, `CborSerializer`, `SerializationContext`.
- [Version](version.md) — the `Version` value object.
- [Helpers](helpers.md) — `getFunctionInfo`, `getPublicProperties`, `hasTrait`, `useConstantsTrait`.

## Requirements

- PHP **8.4+**
- `oihana/php-core`

## License

[MPL-2.0](../../LICENSE) — © Marc Alcaraz (ekameleon).
