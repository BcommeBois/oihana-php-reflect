# Hydration — attributes

[← Back to index](../README.md) · [← Hydration overview](README.md)

All attributes live in `oihana\reflect\attributes`. They are **declarative** — you put them on properties and the hydrator reads them.

## `#[HydrateKey]`

Maps a property to a **different source key** in the input data (e.g. snake_case payloads, database column names).

```php
use oihana\reflect\attributes\HydrateKey;

class User
{
    #[HydrateKey( 'user_name' )]
    public string $name = '';
}

new Reflection()->hydrate( [ 'user_name' => 'Bob' ] , User::class )->name; // 'Bob'
```

## `#[HydrateWith]`

Declares the **class(es)** used to hydrate the items of an `array` property. Supports **polymorphic** collections.

```php
use oihana\reflect\attributes\HydrateWith;

class Address { public string $city = ''; }

class Place
{
    #[HydrateWith( Address::class )]
    public array $locations = [];
}

$place = new Reflection()->hydrate(
    [ 'locations' => [ [ 'city' => 'Paris' ], [ 'city' => 'Lyon' ] ] ],
    Place::class
);
$place->locations[1]->city; // 'Lyon'
```

### Polymorphism

When several classes are possible, the target for each item is chosen by:

1. a **discriminator key** in the item — `@type`, `type` or `atType` — matched (case-insensitively) against the short or fully-qualified class name;
2. otherwise a **property-based guess** (the class whose properties best match the item's keys), falling back to the first declared class.

```php
class Person       { public string $name = ''; }
class Organization { public string $name = ''; }

class Container
{
    #[HydrateWith( Person::class , Organization::class )]
    public array $members = [];
}

new Reflection()->hydrate(
[
    'members' =>
    [
        [ '@type' => 'Person'       , 'name' => 'Alice' ],
        [ '@type' => 'Organization' , 'name' => 'Acme'  ],
    ]
] , Container::class );
```

> Arrays of **backed enums** are also supported when a single enum class is given:
> `#[HydrateWith( Status::class )] public array $history;` resolves each scalar item via `Status::from()`.

## `#[HydrateAs]`

Forces a **target class** when the property type is ambiguous (`object`, `array`, `mixed`, or a union). It also forces a `DateTimeInterface` conversion that the "scalar wins" rule would otherwise skip (see [Types → dates](types.md#datetimeinterface)).

```php
use oihana\reflect\attributes\HydrateAs;

class QuantitativeValue { public float $value = 0.0; }

class Offer
{
    #[HydrateAs( QuantitativeValue::class )]
    public null|array|QuantitativeValue $eligibleQuantity = null;
}
```

## PHPDoc alternative — `@var Type[]`

Instead of `#[HydrateWith]` for a single item class, you can document the element type. **Use the fully-qualified name** (the doc-comment is read literally):

```php
class Geo
{
    /** @var \App\Model\Address[] */
    public array $locations = [];
}
```

Both `@var Type[]` and `@var array<Type>` are recognized. Item classes may be objects, **backed enums** or **dates**.

## `#[Transient]` / `#[HydrateIgnore]`

Excludes a **public property** from **both** directions:

- hydration (input) — the value is never read from the data;
- serialization (output) — `ReflectionTrait::toArray()` never emits it.

The two names are **equivalent aliases** (`HydrateIgnore` extends `Transient`); use whichever reads best.

```php
use oihana\reflect\attributes\Transient;
use oihana\reflect\attributes\HydrateIgnore;

class Invoice
{
    public float $subtotal = 0.0;
    public float $tax      = 0.0;

    #[Transient]
    public float $total = 0.0;        // computed elsewhere

    #[HydrateIgnore]
    public ?string $cachedToken = null;
}

$invoice = new Reflection()->hydrate(
    [ 'subtotal' => 100, 'tax' => 20, 'total' => 999 ],
    Invoice::class
);
$invoice->total; // 0.0 — the incoming 999 was ignored
```

Typical use: **computed / derived** properties that must not be populated from data nor exposed in the serialized form.

## See also

- [Types](types.md) — how typed values are resolved.
- [Reflection API → toArray](../reflection.md) — serialization options.
