<?php

namespace oihana\reflect\utils;

use JsonException;

use oihana\reflect\Reflection;
use ReflectionException;

/**
 * JsonSerializer is an helper class to serialize objects into JSON while allowing temporary,
 * global serialization options.
 *
 * This class acts as a wrapper around `json_encode()` and provides:
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
 * use oihana\reflect\utils\JsonSerializer;
 * use org\schema\Thing;
 *
 * $person1 = new Person(['name' => 'Alice', 'age' => 30]);
 * $person2 = new Person(['name' => 'Bob', 'age' => null]);
 *
 * // Temporarily remove null values during JSON serialization
 * echo JsonSerializer::encode([$person1, $person2], [ArrayOption::REDUCE => true]);
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
final class JsonSerializer
{
    /**
     * Encode data to JSON with temporary options applied
     *
     * @param array|object $data      Object or array of Thing instances.
     * @param int          $jsonFlags JSON encode flags.
     * @param array        $options   Temporary options for extends the serialization behavior.
     *
     * @return string JSON string
     */
    public static function encode( mixed $data , int $jsonFlags = 0  , array $options = [] ) :string
    {
        $previous = SerializationContext::getOptions() ;

        SerializationContext::setOptions( $options );

        try
        {
            return json_encode( $data , $jsonFlags ) ;
        }
        finally
        {
            SerializationContext::reset( $previous ) ;
        }
    }

    /**
     * Decodes a JSON string into an array, or directly into a hydrated object.
     *
     * Invalid JSON fails loud: a {@see JsonException} is always thrown on malformed input
     * (`JSON_THROW_ON_ERROR` is forced).
     *
     * @param string $json The JSON string to decode.
     * @param string|null $class Optional fully-qualified class name. When provided, the decoded
     *                           associative array is hydrated into an instance of that class via
     *                           {@see Reflection::hydrate()}. When null, the decoded value is returned as-is.
     * @param int $flags Additional `json_decode()` flags (associative mode is always enabled).
     *
     * @return mixed The decoded array/value, or the hydrated object when `$class` is given.
     *
     * @throws JsonException If the JSON is malformed.
     * @throws ReflectionException
     *
     * @example
     * ```php
     * JsonSerializer::decode( '{"name":"Alice"}' );              // ['name' => 'Alice']
     * JsonSerializer::decode( '{"name":"Alice"}' , User::class ); // User { name: 'Alice' }
     * ```
     */
    public static function decode( string $json , ?string $class = null , int $flags = 0 ) : mixed
    {
        $data = json_decode( $json , true , 512 , $flags | JSON_THROW_ON_ERROR ) ;

        return $class !== null
             ? new Reflection()->hydrate( $data , $class )
             : $data ;
    }

    /**
     * Get current temporary options
     */
    public static function getOptions(): array
    {
        return SerializationContext::getOptions() ;
    }
}