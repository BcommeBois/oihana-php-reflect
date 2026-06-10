# Hydration — performance

[← Back to index](../README.md) · [← Hydration overview](README.md)

## The per-class hydration plan

The first time a class is hydrated, `Reflection` builds a **hydration plan** and caches it. The plan holds everything that depends only on the class definition — never on the data:

- the **source key** of each property (resolved `#[HydrateKey]`);
- the declared **types** and builtin type names;
- the resolved `#[HydrateWith]` / `#[HydrateAs]` classes;
- the `@var` item class (the doc-comment regex is run **once**);
- the constructor strategy (`new` vs `newInstanceWithoutConstructor`);
- visibility flags, and which properties are `#[Transient]`.

Every subsequent object of the same class reuses this plan instead of re-reading attributes, doc-comments and constructor metadata.

## Why it matters

On a workload that hydrates many objects of the same (nested) class — e.g. a large database result set — this removes the repeated reflection work. Measured on a representative nested document hydrated 10,000 times:

| | Time (10k docs) | Per doc |
|---|---|---|
| Without plan cache | ~800 ms | ~80 µs |
| With plan cache | ~517 ms | ~52 µs |

That is roughly **−35 %**. The deeper the nesting, the larger the gain (each nested class also benefits).

## How to benefit from it

The cache lives on the `Reflection` **instance**. To reuse it across many hydrations, reuse the same instance:

```php
use oihana\reflect\Reflection;

$reflection = new Reflection();              // build once

foreach ( $rows as $row )
{
    $items[] = $reflection->hydrate( $row , Product::class ); // plan reused
}
```

When you hydrate through [`ReflectionTrait`](../reflection.md), the trait already holds a single shared `Reflection` instance, so nested and repeated hydrations benefit automatically.

## Characteristics

- **In-memory**, no external store (no APCu/Redis/etc.).
- **Bounded** by the number of distinct hydrated classes (one entry per class, not per object) — typically a few KB total.
- **No eviction needed**: PHP frees everything at the end of the request/process. The behavior is identical with or without the cache — it only removes redundant work.

## Clearing the cache

Usually unnecessary, but in tests or long-running workers (RoadRunner, Swoole, queues) you can drop every cached entry — both the `ReflectionClass` instances and the hydration plans — with:

```php
$reflection->clearCache(); // caches are transparently rebuilt on the next call
```
