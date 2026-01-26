<?php

namespace oihana\reflect\utils;

/**
 * SerializationContext is a global helper that stores temporary serialization options
 * during an encoding scope.
 *
 * This class centralizes all transient serialization configuration shared between
 * different serializers (JSON, CBOR, etc.) and the domain objects themselves.
 *
 * Typical usage:
 * - A serializer (JsonSerializer, CborSerializer, …) sets options at the beginning
 *   of an encode() call.
 * - Domain objects (via ThingTrait::getJsonSerializeOptions()) read these options
 *   while being serialized.
 * - The previous context is restored using a try/finally block to guarantee
 *   proper cleanup even in case of error.
 *
 * This design ensures:
 * - A single source of truth for serialization options.
 * - Safe scoping of global state.
 * - Consistent behavior across all serialization formats.
 *
 * @author  Marc Alcaraz (eKameleon)
 * @package oihana\reflect\utils
 * @since   1.0.4
 */
final class SerializationContext
{
    /**
     * Temporary options applied during the current serialization scope.
     *
     * These options are intended to be short-lived and must be set and restored
     * by serializers using a try/finally pattern.
     *
     * @var array<string, mixed>
     */
    private static array $temporaryOptions = [];

    /**
     * Returns the current global serialization context options.
     *
     * This method is typically called from domain objects (e.g. in jsonSerialize())
     * to retrieve the active temporary configuration.
     *
     * @return array<string, mixed> The current serialization options.
     */
    public static function getOptions(): array
    {
        return self::$temporaryOptions;
    }

    /**
     * Resets the global serialization context options.
     *
     * This method is intended to be used in a finally block to restore the
     * previous serialization context after an encode() call.
     *
     * @param array<string, mixed> $previous The previous options to restore.
     *
     * @return void
     */
    public static function reset( array $previous = [] ) :void
    {
        self::$temporaryOptions = $previous;
    }

    /**
     * Sets the global serialization context options.
     *
     * This should only be called by serializer entry points (JsonSerializer,
     * CborSerializer, etc.) at the beginning of an encoding operation.
     *
     * @param array<string, mixed> $options The temporary serialization options.
     *
     * @return void
     */
    public static function setOptions(array $options): void
    {
        self::$temporaryOptions = $options;
    }
}