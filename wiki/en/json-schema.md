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

## Enum names

The keywords, types and draft versions are exposed as named constants:

- `oihana\reflect\enums\JsonSchemaDraft` — draft versions;
- `oihana\reflect\enums\JsonSchemaKeyword` — schema keywords;
- `oihana\reflect\enums\JsonSchemaType` — schema types;
- `oihana\reflect\enums\PhpType` — the main PHP type names.

> Note: the generator maps PHP property types to JSON Schema types. Awareness of richer hydration conventions (backed-enum `enum` constraints, `#[HydrateKey]` renames, `date-time` formats) is tracked as a future enhancement.
