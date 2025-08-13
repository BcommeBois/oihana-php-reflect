# Oihana PHP - Reflect

![Oihana PHP - Reflect](https://raw.githubusercontent.com/BcommeBois/oihana-php-reflect/main/assets/images/oihana-php-reflect-logo-inline-512x160.png)

[![Latest Version](https://img.shields.io/packagist/v/oihana/php-reflect.svg?style=flat-square)](https://packagist.org/packages/oihana/php-reflect)
[![Total Downloads](https://img.shields.io/packagist/dt/oihana/php-reflect.svg?style=flat-square)](https://packagist.org/packages/oihana/php-reflect)
[![License](https://img.shields.io/packagist/l/oihana/php-reflect.svg?style=flat-square)](LICENSE)

Lightweight reflection and hydration helpers for modern PHP, part of the Oihana PHP ecosystem. 

It provides:

- Friendly wrappers around PHP's Reflection API
- Array-to-object hydration with attribute-based mapping
- Utilities to expose public properties as arrays
- Simple constant “enums” helpers
- A compact `Version` value object

## 📚 Documentation

Full documentation: `https://bcommebois.github.io/oihana-php-reflect`

## 📦 Installation

Requires PHP 8.4+

```bash
composer require oihana/php-reflect
```

## ✨ Features

Reflection helpers
  
- List constants, methods, properties with visibility filters
- Inspect method parameters: type, default, nullable, optional, variadic
- Describe any callable’s parameters (`describeCallableParameters`)
- Cached `ReflectionClass` instances

Hydration

- Instantiate and hydrate objects from associative arrays (recursively)
- Supports union types and nullability
- Attribute-based mapping:
  - `#[HydrateKey('source_key')]` to rename incoming keys
  - `#[HydrateWith(Foo::class, Bar::class)]` for arrays of objects, including polymorphism via `@type`/`type` or property-guessing
  - `#[HydrateAs(Foo::class)]` to override ambiguous types (`object`, `array`, `mixed`, unions)
- PHPDoc `@var Type[]` and `@var array<Type>` support for array element types

Traits

- `ReflectionTrait` convenience layer and `jsonSerializeFromPublicProperties()` (with optional reduction)
- `ConstantsTrait` utilities over class constants: `getAll`, `includes`, `enums`, `getConstant`, `validate`

Value objects
- `Version` packs major/minor/build/revision into a 32-bit int with configurable string output

## 🚀 Quick start

### Reflection basics
```php
use oihana\reflect\Reflection;

$ref = new Reflection();

// Constants
$constants = $ref->constants(MyEnum::class); // ['ACTIVE' => 'active']

// Methods / Properties
$methods = $ref->methods(MyClass::class);
$props   = $ref->properties(MyDto::class);

// Parameters inspection
$type     = $ref->parameterType(MyClass::class, 'setName', 'name'); // 'string'
$default  = $ref->parameterDefaultValue(MyClass::class, 'setAge', 'age'); // 30
$nullable = $ref->isParameterNullable(MyClass::class, 'setNickname', 'nickname'); // true
```

### Describe any callable
```php
$fn = fn(string $name, int $age = 42, ...$tags) => null;
$params = (new Reflection())->describeCallableParameters($fn);
/*
[
  ['name' => 'name', 'type' => 'string', 'optional' => false, 'nullable' => false, 'variadic' => false],
  ['name' => 'age',  'type' => 'int',    'optional' => true,  'nullable' => false, 'variadic' => false, 'default' => 42],
  ['name' => 'tags', 'type' => null,     'optional' => false, 'nullable' => false, 'variadic' => true],
]
*/
```

### Hydration: flat and nested
```php
class Address { public string $city; }
class User { public string $name; public ?Address $address = null; }

$data = ['name' => 'Alice', 'address' => ['city' => 'Paris']];
$user = (new Reflection())->hydrate($data, User::class);
```

### Hydration with attributes
```php
use oihana\reflect\attributes\{HydrateKey, HydrateWith, HydrateAs};

class WithKey { #[HydrateKey('user_name')] public string $name; }
// Maps input key 'user_name' to property 'name'

class Geo { #[HydrateWith(Address::class)] public array $locations = []; }
// Hydrates each element of an array property as Address

class Wrapper { #[HydrateAs(Address::class)] public object $payload; }
// Overrides ambiguous type (object/array/mixed/union)
```

### Arrays of objects via PHPDoc
```php
class Address { public string $city; }
class Geo { /** @var Address[] */ public array $locations = []; }

$geo = (new Reflection())->hydrate(
  ['locations' => [ ['city' => 'Lyon'], ['city' => 'Nice'] ]],
  Geo::class
);
```

### Polymorphic arrays with HydrateWith
```php
class A { public string $type = 'A'; }
class B { public string $type = 'B'; }
class Box { #[HydrateWith(A::class, B::class)] public array $items = []; }

// Chooses the right class using '@type' or 'type', or best-guess by properties
```

### Trait: ReflectionTrait
```php
use oihana\reflect\traits\ReflectionTrait;

class Product {
    use ReflectionTrait;
    public string $name = 'Book';
    public ?string $desc = null;
}

$p = new Product();
$data = $p->jsonSerializeFromPublicProperties(Product::class, true); // ['name' => 'Book']
```

### Trait: ConstantsTrait
```php
use oihana\reflect\traits\ConstantsTrait;

final class Status { use ConstantsTrait; public const string OPEN = 'open'; public const string CLOSED = 'closed'; }

Status::includes('open'); // true
Status::enums();           // ['closed', 'open'] (sorted unique values)
Status::getConstant('open'); // 'OPEN'
```

### Value object: Version
```php
use oihana\reflect\Version;

$v = new Version(1, 2, 3, 4);
$v->fields = 3;            // print as 1.2.3
echo (string) $v;          // "1.2.3"
$v->major = 2;             // mutate safely
$n = $v->valueOf();        // packed 32-bit int
```

## ✅ Running Unit Tests

```bash
composer test
```

Run a specific test file:
```bash
composer test ./tests/oihana/reflect/VersionTest.php
```

## 🛠️ Generate the API Docs

We use phpDocumentor to generate the HTML docs into `./docs`.

```bash
composer doc
```

## 🧾 License

Licensed under the Mozilla Public License 2.0 (MPL-2.0). See `LICENSE`.

## 👤 Author

- Author: Marc ALCARAZ (aka eKameleon)
- Email: marc@ooop.fr
- Website: `http://www.ooop.fr`