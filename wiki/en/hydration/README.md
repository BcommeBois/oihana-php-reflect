# Hydration — overview

[← Back to index](../README.md)

Hydration turns an **associative array** (a JSON payload, a database row, a config block) into a **typed object graph**. It is the core feature of `oihana/php-reflect`.

```php
use oihana\reflect\Reflection;

$object = new Reflection()->hydrate( $data , MyClass::class );
```

You can also use it through [`ReflectionTrait`](../reflection.md):

```php
class MyClass
{
    use \oihana\reflect\traits\ReflectionTrait;
}

$object = new MyClass()->hydrate( $data , MyClass::class );
```

## What it does

For each **public property** of the target class, the hydrator:

1. resolves the **source key** in the data — the property name, or an alias declared with [`#[HydrateKey]`](attributes.md#hydratekey);
2. skips the property if the key is absent (the declared default is kept), or if it is marked [`#[Transient]` / `#[HydrateIgnore]`](attributes.md#transient--hydrateignore);
3. resolves the value according to the property's declared **type** (see [Types](types.md)):
   - nested object → recursive `hydrate()`;
   - array of objects → via [`#[HydrateWith]`](attributes.md#hydratewith) or a `@var Type[]` doc-comment;
   - backed enum → `Enum::from()`;
   - `DateTimeInterface` → parsed date / Unix timestamp;
   - scalar → PHP coercive typing;
4. assigns the value through reflection (so `readonly` and asymmetric-visibility properties work too).

## Design rules (worth knowing)

- **Public properties only.** Private/protected properties are ignored by design.
- **Recursive.** Nested objects and arrays of objects are hydrated all the way down.
- **Union types & nullability are honored.** A scalar member of a union "wins" over an ambiguous class conversion (see [Types → unions](types.md#unions--nullability)).
- **Fail loud.** Invalid data raises a single [`HydrationException`](errors.md) — never a silent wrong value.
- **Cached.** A per-class [hydration plan](performance.md) is computed once and reused for every object.

## A fuller example

```php
use oihana\reflect\Reflection;
use oihana\reflect\attributes\HydrateKey;
use oihana\reflect\attributes\HydrateWith;
use oihana\reflect\attributes\Transient;

enum Role : string { case Admin = 'admin'; case Member = 'member'; }

class Tag    { public string $label = ''; }
class Member
{
    #[HydrateKey( '_key' )]
    public string $id = '';
    public Role   $role = Role::Member;
    public ?DateTimeImmutable $joinedAt = null;

    /** @var \Tag[] */
    public array $tags = [];

    #[Transient]                 // computed; never read from data nor serialized
    public int $score = 0;
}

$member = new Reflection()->hydrate(
[
    '_key'     => 'u-1',
    'role'     => 'admin',
    'joinedAt' => '2024-03-01T12:00:00+00:00',
    'tags'     => [ [ 'label' => 'php' ], [ 'label' => 'arango' ] ],
    'score'    => 999,          // ignored (Transient)
] , Member::class );

$member->id;          // 'u-1'
$member->role;        // Role::Admin
$member->joinedAt;    // DateTimeImmutable
$member->tags[0];     // Tag { label: 'php' }
$member->score;       // 0  (not overwritten)
```

## Next

- [Attributes](attributes.md) — declarative mapping.
- [Types](types.md) — enums, dates, unions, coercion, `readonly`, constructors.
- [Errors](errors.md) — `HydrationException`.
- [Performance](performance.md) — the plan cache.
