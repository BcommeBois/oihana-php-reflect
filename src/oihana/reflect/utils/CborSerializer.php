<?php

namespace oihana\reflect\utils;

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
 * @since   1.0.4
 */
final class CborSerializer
{
    /**
     * Encode data to cbor with temporary options applied
     *
     * @param array|object $data    Object or array of Thing instances.
     * @param array        $options Temporary options for extends the serialization behavior.
     *
     * @return string JSON string
     */
    public static function encode( mixed $data , array $options = [] ) :string
    {
        $previous = SerializationContext::getOptions() ;

        SerializationContext::setOptions( $options ) ;

        try
        {
            return cbor_encode
            (
                $data ,
                fn( mixed $v ): string => JsonSerializer::encode( $v )
            );
        }
        finally
        {
            SerializationContext::reset( $previous ) ;
        }
    }
}