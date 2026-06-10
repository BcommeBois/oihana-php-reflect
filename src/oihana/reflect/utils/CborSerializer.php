<?php

namespace oihana\reflect\utils;

use Closure;

use oihana\reflect\Reflection;

use ReflectionException;
use function oihana\core\cbor\cbor_decode;
use function oihana\core\cbor\cbor_encode;

/**
 * CborSerializer is an helper class to serialize objects
 * into cbor while allowing temporary, global serialization options.
 *
 * This class acts as a wrapper around {@see cbor_encode()} and provides:
 * - Temporary global options applied to all Thing objects during serialization.
 * - Safe scoping of options using `try/finally` to ensure they are reset.
 * - Compatibility with arrays or single objects.
 *
 * The main purpose is to separate serialization logic and option management
 * from the Thing classes themselves, keeping traits minimal and focused on data.
 *
 * Example usage:
 * ```php
 * use oihana\core\options\ArrayOption;
 * use oihana\reflect\utils\CborSerializer;
 * use org\schema\Thing;
 *
 * $person1 = new Person(['name' => 'Alice', 'age' => 30]);
 * $person2 = new Person(['name' => 'Bob', 'age' => null]);
 *
 * // Temporarily remove null values during JSON serialization
 * echo CborSerializer::encode([$person1, $person2], [ArrayOption::REDUCE => true]);
 * ```
 *
 * Notes:
 * - Options are **global for the duration of the encode() call** and automatically
 *   reset afterward.
 * - This is especially useful for JSON-LD serialization where all Thing objects
 *   need consistent formatting rules.
 *
 * @author  Marc Alcaraz (eKameleon)
 * @package oihana\reflect\utils
 * @since   1.1.0
 */
final class CborSerializer
{
    /**
     * Encode data to cbor with temporary options applied
     *
     * @param array|object $data    Object or array of Thing instances.
     * @param array        $options Temporary options for extends the serialization behavior.
     * @param Closure|null $replacer Optional callback applied to each encoded value: fn($key, $value)
     *
     * @return string JSON string
     */
    public static function encode( mixed $data , array $options = [] , ?Closure $replacer = null ) :string
    {
        $previous = SerializationContext::getOptions() ;

        SerializationContext::setOptions( $options ) ;

        try
        {
            return cbor_encode( $data , $replacer ) ;
        }
        finally
        {
            SerializationContext::reset( $previous ) ;
        }
    }

    /**
     * Decodes a CBOR string into an array/value, or directly into a hydrated object.
     *
     * @param string $data The CBOR-encoded string to decode.
     * @param string|null $class Optional fully-qualified class name. When provided, the decoded
     *                               associative array is hydrated into an instance of that class via
     *                               {@see Reflection::hydrate()}. When null, the decoded value is returned as-is.
     * @param Closure|null $replacer Optional callback applied to each decoded value: fn($key, $value).
     *
     * @return mixed The decoded array/value, or the hydrated object when `$class` is given.
     *
     * @throws ReflectionException
     *
     * @example
     * ```php
     * $bytes = CborSerializer::encode( $user );
     * $user2 = CborSerializer::decode( $bytes , User::class ); // full round-trip
     * ```
     */
    public static function decode( string $data , ?string $class = null , ?Closure $replacer = null ) : mixed
    {
        $decoded = cbor_decode( $data , $replacer ) ;

        return $class !== null ? new Reflection()->hydrate( $decoded , $class ) : $decoded ;
    }
}