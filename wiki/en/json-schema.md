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

## Enum names

The keywords, types and draft versions are exposed as named constants:

- `oihana\reflect\enums\JsonSchemaDraft` — draft versions;
- `oihana\reflect\enums\JsonSchemaKeyword` — schema keywords;
- `oihana\reflect\enums\JsonSchemaType` — schema types;
- `oihana\reflect\enums\PhpType` — the main PHP type names.

> Note: the generator maps PHP property types to JSON Schema types, including backed-enum `enum` constraints. Awareness of the remaining richer hydration conventions (`#[HydrateKey]` renames, `date-time` formats, typed-array `items`) is tracked as a future enhancement.
