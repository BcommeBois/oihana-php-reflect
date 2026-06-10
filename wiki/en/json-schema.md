# JSON Schema

[← Back to index](README.md)

`oihana\reflect\traits\JsonSchemaTrait` generates a **JSON Schema (draft 2020-12)** from a class's public properties, and validates data against it. It introspects property types, nullability and doc-comments.

```php
use oihana\reflect\traits\JsonSchemaTrait;

class User
{
    use JsonSchemaTrait;

    /** The user's full name. */
    public string  $name;
    public ?int    $age = null;
    public bool    $active = true;
}
```

## Generating a schema

```php
User::jsonSchema();          // static — array (the JSON Schema)
new User()->toJsonSchema();  // instance — same result
```

The generated schema includes property types, required/nullable information and descriptions extracted from doc-comments. Pass `strict: false` to relax the generation.

## Validating data

```php
$errors = User::validateWithJsonSchema( [ 'name' => 'Alice', 'age' => 30 ] );      // static
$errors = new User()->validateDataWithJsonSchema( [ 'name' => 'Alice' ] );          // instance
```

These return the list of validation errors (empty when the data is valid).

## Enum-typed properties

A property typed with a PHP enum is described by what `hydrate()` actually accepts, rather than as an opaque object `$ref`.

A **backed enum** maps to its scalar backing type plus the `enum` keyword listing the case values:

```php
enum Status: string { case Active = 'active'; case Inactive = 'inactive'; }
enum Priority: int  { case Low = 1; case High = 10; }

class Task
{
    use JsonSchemaTrait;

    public Status    $status;            // { "type": "string",  "enum": ["active", "inactive"] }
    public ?Priority $priority = null;   // { "oneOf": [ { "type": "null" }, { "type": "integer", "enum": [1, 10] } ] }
}
```

A **pure (non-backed) enum** has no scalar representation, so it cannot be hydrated from data. Its case names are still listed for documentation and a `$comment` flags the limitation:

```php
enum Color { case Red; case Blue; }

// public Color $color;
// {
//     "type": "string",
//     "enum": ["Red", "Blue"],
//     "$comment": "Pure (non-backed) enum: not hydratable from a scalar value."
// }
```

## Date and time properties

A property typed with any `DateTimeInterface` implementation (`DateTime`, `DateTimeImmutable`, or the interface itself) maps to a string carrying the `date-time` format — matching the ISO 8601 string that `hydrate()` parses:

```php
use DateTimeImmutable;

class Event
{
    use JsonSchemaTrait;

    public DateTimeImmutable  $createdAt;          // { "type": "string", "format": "date-time" }
    public ?DateTime          $updatedAt = null;   // { "oneOf": [ { "type": "null" }, { "type": "string", "format": "date-time" } ] }
}
```

This applies to standalone date-typed properties. In a union that also accepts a scalar (e.g. `string|DateTimeInterface`), `hydrate()` keeps the raw value as-is, so no `format` constraint is emitted.

## Typed arrays

An `array` property whose element type is known describes that element under the `items` keyword. The element type is resolved exactly as `hydrate()` resolves it — from a `#[HydrateWith]` attribute first, then from a `@var Type[]` / `@var array<Type>` doc-block — and each element is mapped like a standalone property (enum, `date-time`, or object `$ref`):

```php
use oihana\reflect\attributes\HydrateWith;

class Catalog
{
    use JsonSchemaTrait;

    #[HydrateWith( Product::class )]
    public array $products;        // { "type": "array", "items": { "type": "object", "$ref": "#/definitions/Product" } }

    /** @var \App\Status[] */
    public array $statuses;        // items: { "type": "string", "enum": [ ... ] }

    /** @var \DateTimeImmutable[] */
    public array $dates;           // items: { "type": "string", "format": "date-time" }
}
```

A polymorphic `#[HydrateWith(A::class, B::class)]` yields `items: { "oneOf": [ { "$ref": ... }, { "$ref": ... } ] }`. Untyped arrays — and arrays of scalars (which `hydrate()` leaves untouched) — stay `{ "type": "array" }` with no `items`.

## Renamed properties (`#[HydrateKey]`)

When a property declares a `#[HydrateKey]` source key, the schema names that property after the **primary source key** — the same key `hydrate()` reads from the input — instead of the PHP property name. Validation follows suit: data must use the source key.

```php
use oihana\reflect\attributes\HydrateKey;

class Account
{
    use JsonSchemaTrait;

    #[HydrateKey( 'user_name' )]
    public string $name;                       // schema property: "user_name"

    #[HydrateKey( 'created_on', 'createdOn' )]
    public ?string $createdAt = null;          // primary key wins -> "created_on"
}
```

With several fallback keys, the first one (the primary key) is used in the schema.

## Enum names

The keywords, types and draft versions are exposed as named constants:

- `oihana\reflect\enums\JsonSchemaDraft` — draft versions;
- `oihana\reflect\enums\JsonSchemaKeyword` — schema keywords;
- `oihana\reflect\enums\JsonSchemaType` — schema types;
- `oihana\reflect\enums\JsonSchemaFormat` — the standard string formats (e.g. `date-time`);
- `oihana\reflect\enums\PhpType` — the main PHP type names.

> Note: the generated schema mirrors what `hydrate()` accepts — backed-enum `enum` constraints, `date-time` formats for `DateTimeInterface` properties, `items` for typed arrays, and `#[HydrateKey]` source-key renames.
