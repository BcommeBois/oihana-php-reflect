# Serialization

[← Back to index](README.md)

The library ships two thin serializers that apply **temporary, scoped serialization options** while encoding, plus the shared context that carries them.

## JsonSerializer

`oihana\reflect\utils\JsonSerializer` wraps `json_encode()` and applies options for the duration of the call (then restores the previous context):

```php
use oihana\core\options\ArrayOption;
use oihana\reflect\utils\JsonSerializer;

echo JsonSerializer::encode( [ $person1, $person2 ] , JSON_PRETTY_PRINT , [ ArrayOption::REDUCE => true ] );
```

- `encode( mixed $data , int $jsonFlags = 0 , array $options = [] ) : string`
- `decode( string $json , ?string $class = null , int $flags = 0 ) : mixed` — decode a JSON string into an array, or directly into a hydrated object when `$class` is given. Malformed JSON **fails loud** (`JSON_THROW_ON_ERROR` is forced → `JsonException`).
- `getOptions() : array` — the current temporary options.

```php
JsonSerializer::decode( '{"name":"Alice"}' );               // ['name' => 'Alice']
JsonSerializer::decode( '{"name":"Alice"}' , User::class ); // User { name: 'Alice' } (enums/dates resolved by hydrate)
```

The options are global **for the duration of the `encode()` call** only, and are reset afterwards (even on error). This is handy when many objects must share consistent formatting rules during a single encode (e.g. JSON-LD output).

## CborSerializer

`oihana\reflect\utils\CborSerializer` does the same for CBOR, wrapping `oihana\core\cbor\cbor_encode()`:

```php
use oihana\reflect\utils\CborSerializer;

$bytes = CborSerializer::encode( $data , [ ArrayOption::REDUCE => true ] );
```

- `encode( mixed $data , array $options = [] , ?Closure $replacer = null ) : string`

An optional `$replacer` callback `fn( $key , $value )` can transform each encoded value.

## SerializationContext

`oihana\reflect\utils\SerializationContext` is the single source of truth for the transient options shared between serializers and domain objects during an encoding scope:

```php
use oihana\reflect\utils\SerializationContext;

SerializationContext::getOptions();        // read the active options (e.g. inside jsonSerialize())
SerializationContext::setOptions( $opts ); // set (serializer entry points only)
SerializationContext::reset( $previous );  // restore (in a finally block)
```

Typically you don't call it directly — the serializers manage it via try/finally. Domain objects read `SerializationContext::getOptions()` inside their `jsonSerialize()` to honor the active options.

> A `CborSerializer::decode()` counterpart is planned. To turn decoded CBOR back into typed objects today, decode with `cbor_decode($bytes)` and pass the array to [`Reflection::hydrate()`](hydration/README.md).
