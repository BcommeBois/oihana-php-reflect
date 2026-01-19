<?php

namespace oihana\reflect\utils;

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
 * @since   1.0.4
 */
final class JsonSerializer
{
    /**
     * Temporary options applied to all Thing objects during serialization
     * @var array
     */
    private static array $temporaryOptions = [] ;

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
        $previous = self::$temporaryOptions ;

        self::$temporaryOptions = $options ;

        try
        {
            return json_encode( $data , $jsonFlags ) ;
        }
        finally
        {
            self::$temporaryOptions = $previous ;
        }
    }

    /**
     * Get current temporary options
     */
    public static function getOptions(): array
    {
        return self::$temporaryOptions ;
    }
}