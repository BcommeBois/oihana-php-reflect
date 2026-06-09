# Hydration — errors

[← Back to index](../README.md) · [← Hydration overview](README.md)

Every hydration failure is reported through a single, catchable type: **`oihana\reflect\exceptions\HydrationException`**.

## What it unifies

`HydrationException` is raised for:

- a **missing class** (`hydrate($data, 'NoSuchClass')`);
- a **non-nullable** property set to `null`;
- an **invalid backed-enum** value (wraps the underlying `ValueError`);
- a **pure (non-backed) enum** hydrated from a scalar;
- a **non-coercible scalar** (e.g. `'abc'` → `int`, wraps the underlying `TypeError`);
- an **unparsable date**;
- any other failure occurring while assigning a property.

## API

```php
namespace oihana\reflect\exceptions;

class HydrationException extends \InvalidArgumentException
{
    public function getClassName(): ?string;     // FQCN being hydrated (if known)
    public function getPropertyName(): ?string;  // property that failed (if applicable)
    // getPrevious() returns the wrapped low-level error (ValueError, TypeError, ...)
}
```

It **extends `InvalidArgumentException`**, so existing `catch (InvalidArgumentException)` or `catch (Throwable)` code keeps working unchanged.

## Handling a single record

```php
use oihana\reflect\Reflection;
use oihana\reflect\exceptions\HydrationException;

try
{
    $product = new Reflection()->hydrate( $row , Product::class );
}
catch ( HydrationException $e )
{
    $logger->warning( sprintf(
        'Hydration failed for %s::$%s — %s',
        $e->getClassName(),
        $e->getPropertyName(),
        $e->getMessage()
    ) );
    // inspect the root cause:
    $cause = $e->getPrevious(); // e.g. ValueError / TypeError, or null
}
```

## Skipping invalid records in a batch / stream

The single catchable type makes it easy to stay resilient when hydrating many documents (e.g. a database result set):

```php
$valid = [];

foreach ( $rows as $row )
{
    try
    {
        $valid[] = new Reflection()->hydrate( $row , Product::class );
    }
    catch ( HydrationException $e )
    {
        $logger->notice( 'Skipped invalid row: ' . $e->getMessage() );
    }
}
```

## See also

- [Types](types.md) — which values are valid for each declared type.
