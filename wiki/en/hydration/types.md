# Hydration — types

[← Back to index](../README.md) · [← Hydration overview](README.md)

The hydrator resolves each value according to the property's **declared type**. This page covers every supported kind.

## Nested objects

A property typed as a class is hydrated recursively from an associative array:

```php
class Address { public string $city = ''; }
class User    { public ?Address $address = null; }

new Reflection()->hydrate( [ 'address' => [ 'city' => 'Paris' ] ] , User::class )
    ->address->city; // 'Paris'
```

## Arrays of objects

Use [`#[HydrateWith]`](attributes.md#hydratewith) or a `@var Type[]` doc-comment. A list of associative arrays becomes a list of objects.

## Backed enums

A scalar (string or int) targeting a **backed enum** is resolved with `Enum::from()`:

```php
enum Status : string { case Active = 'active'; case Inactive = 'inactive'; }

class User { public Status $status = Status::Inactive; }

new Reflection()->hydrate( [ 'status' => 'active' ] , User::class )->status; // Status::Active
```

- An **unknown** value fails loud (`Enum::from()` throws, wrapped into a [`HydrationException`](errors.md)).
- A value already holding an enum **instance** is kept as-is.
- Arrays of enums work via `#[HydrateWith(Status::class)]` or `@var Status[]`.

> **Pure (non-backed) enums** have no scalar representation. Hydrating one from a scalar raises a [`HydrationException`](errors.md) — declare a **backed** enum instead.

## `DateTimeInterface`

A property typed `DateTime`, `DateTimeImmutable` (or a subclass), or the `DateTimeInterface` is resolved from:

- a **string** → parsed as a date (ISO 8601 or any format the constructor understands);
- an **int** → a Unix timestamp (seconds).

```php
class Article { public ?DateTimeImmutable $createdAt = null; }

new Reflection()->hydrate( [ 'createdAt' => '2024-01-02T03:04:05+00:00' ] , Article::class )
    ->createdAt; // DateTimeImmutable
```

The concrete class is preserved: `DateTime` stays mutable, `DateTimeImmutable`/subclasses immutable, and the abstract `DateTimeInterface` defaults to `DateTimeImmutable`. An already-constructed date is kept. A numeric timestamp must be passed as an **int** (a numeric *string* is treated as a date string).

### The "scalar wins" rule

If the property is a **union that also accepts a builtin scalar** (e.g. `string|DateTimeInterface`, or the common schema.org shape `null|string|int`), the **raw scalar is kept** — the value is *not* coerced to a date:

```php
class Event
{
    public null|string|DateTimeInterface $endDate = null; // string kept
    public null|string|int               $startDate = null; // never converted
}
```

To force the conversion in such a union, use [`#[HydrateAs(DateTimeImmutable::class)]`](attributes.md#hydrateas). A property typed **strictly** as a date (e.g. `DateTimeImmutable $d`) is always converted.

## Unions & nullability

- `?Type` and `Type|null` are honored — `null` is assigned when allowed.
- Assigning `null` to a **non-nullable** property raises a [`HydrationException`](errors.md).
- For a union containing a builtin scalar, the raw scalar value takes precedence over an ambiguous date conversion (see the rule above).

## Scalar coercion

Scalar values are converted to the declared type following PHP's **coercive typing**:

| Declared | Input | Result |
|---|---|---|
| `int` | `'42'` | `42` |
| `float` | `'3.14'` | `3.14` |
| `bool` | `'1'` / `'0'` | `true` / `false` |
| `string` | `7` | `'7'` |

A value that **cannot** be coerced (e.g. `'abc'` → `int`) raises a [`HydrationException`](errors.md). This behavior is **independent of `strict_types`** (values are assigned through `ReflectionProperty::setValue()`).

## `readonly` & asymmetric-visibility properties

`readonly` properties and PHP 8.4 asymmetric-visibility properties (`public private(set)`, `public protected(set)`) are assigned **through reflection**, so they are initialized correctly:

```php
class Entity
{
    public readonly string  $id;
    public private(set) int $version;
}

$e = new Reflection()->hydrate( [ 'id' => 'abc', 'version' => 3 ] , Entity::class );
$e->id;      // 'abc'
$e->version; // 3
```

## Classes with a required constructor

If the target class declares **required** constructor arguments, the object is created with `newInstanceWithoutConstructor()` and populated from the data — no more `ArgumentCountError`:

```php
class Money
{
    public int $amount = 0;
    public function __construct( public string $currency ) {}
}

new Reflection()->hydrate( [ 'currency' => 'EUR', 'amount' => 100 ] , Money::class );
```

A constructor that is **callable with no arguments** is still invoked normally (its side effects and defaults are preserved). Declared property defaults still apply; a required property absent from the data stays uninitialized.

## See also

- [Attributes](attributes.md) · [Errors](errors.md) · [Performance](performance.md)
