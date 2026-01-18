<?php

namespace oihana\reflect\options;

use function oihana\core\arrays\compress;

/**
 * Defines constants for the options used by JSON serialization helpers
 * based on public object properties and reflection.
 *
 * These options are primarily consumed by
 * `jsonSerializeFromPublicProperties()` and related helpers.
 *
 * Using constants avoids magic strings and provides a stable,
 * self-documented API for controlling JSON output structure,
 * ordering and reduction.
 *
 * Available options:
 *
 * - `BEFORE`
 * Associative array of key/value pairs injected before serialized properties.
 * Typically used for metadata such as `@type` or `@context`.
 *
 * - `AFTER`
 * Associative array of key/value pairs appended after serialized properties.
 *
 * - `INCLUDE`
 * Whitelist of property names to include in serialization.
 * If set, only these properties are serialized.
 *
 * - `EXCLUDE`
 * Blacklist of property names to exclude from serialization.
 *
 * - `PRIORITY_KEYS`
 * List of keys that must appear first in the resulting JSON object,
 * in the given order, before alphabetical sorting is applied.
 *
 * - `SORT`
 * Whether remaining keys should be sorted alphabetically (ksort).
 *
 * - `REDUCE`
 * Controls value reduction using `compress()` semantics.
 * Can be:
 * - `false` : no reduction
 * - `true`  : default reduction
 * - `array` : options forwarded to `compress()`
 *
 * @example Basic usage
 * ```php
 * use oihana\reflect\options\NormalizeOption;
 *
 * $options =
 * [
 *     NormalizeOption::BEFORE =>
 *     [
 *        '@type'    => 'Thing',
 *        '@context' => 'https://schema.org',
 *     ],
 *     JsonSerializeOption::PRIORITY_KEYS => ['@type', '@context'],
 *     JsonSerializeOption::REDUCE        => true,
 * ];
 *
 * $data = NormalizeOption::normalize($options);
 * ```
 *
 * @package oihana\core\options
 * @author  Marc Alcaraz
 * @since   1.0.4
 */
class NormalizeOption
{
    /**
     * Keys/values to append **after** serialized properties.
     * Typically used for metadata that should appear at the end of JSON output.
     *
     * Type: array<string,mixed>
     */
    public const string AFTER = 'after' ;

    /**
     * Keys/values to inject **before** serialized properties.
     * Typically used for metadatas.
     * Type: array<string,mixed>
     */
    public const string BEFORE = 'before' ;

    /**
     * Default values to apply for keys that are missing or null in the serialized output.
     *
     * Type: array<string,mixed>
     *
     * Usage:
     * - Each key represents a property name.
     * - If the property is missing or its value is `null`, the corresponding default value will be used.
     *
     * Example:
     * ```php
     * $options = [
     *     JsonSerializeOption::DEFAULTS => [
     *         'stock' => 0,
     *         'desc'  => 'No description'
     *     ]
     * ];
     *
     * $data = $helper->jsonSerializeFromPublicProperties(Product::class, $options);
     * // 'stock' and 'desc' will get default values if they are null or missing
     * ```
     */
    public const string DEFAULTS = 'defaults' ;

    /**
     * Blacklist of property names to exclude from serialization.
     *
     * Type: string[]|null
     */
    public const string EXCLUDE = 'exclude' ;

    /**
     * List of keys that must appear first in the resulting JSON object,
     * in the given order, before alphabetical sorting is applied.
     *
     * Type: string[]
     */
    public const string FIRST_KEYS = 'firstKeys' ;

    /**
     * Whitelist of property names to include in serialization.
     * If set, only these properties are serialized.
     *
     * Type: string[]|null
     */
    public const string INCLUDE = 'include' ;

    /**
     * Controls value reduction using `compress()` semantics.
     * Can be:
     *   - false : no reduction (default)
     *   - true  : use reduction
     *   - array : options forwarded reduce with the {@see compress()} function.
     *
     * Type: bool|array
     */
    public const string REDUCE = 'reduce' ;

    /**
     * Whether remaining keys should be sorted alphabetically (ksort).
     *
     * Type: bool
     */
    public const string SORT = 'sort' ;

    /**
     * Normalize the options for the reflection jsonSerializeOptions functions.
     *
     * Fills in defaults for missing keys and ensures consistent option names.
     *
     * @param array|null $options User-provided options
     *
     * @return array Normalized options with default values
     *
     * @example
     * ```php
     * $opts = JsonSerializeOption::normalize
     * ([
     *      JsonSerializeOption::BEFORE =>
     *      [
     *        '_type' => 'Thing'
     *      ],
     *      JsonSerializeOption::REDUCE => true
     * ]);
     *
     * // Result:
     * // [
     * //   'after'        => [],
     * //   'before'       => ['_type' => 'Thing'],
     * //   'exclude'      => null,
     * //   'sort'         => true,
     * //   'priorityKeys' => [],
     * //   'reduce'       => true,
     * //   'include'      => null
     * // ]
     * ```
     */
    public static function normalize( ?array $options = [] ) :array
    {
        $options = $options ?? [];
        return
        [
            self::AFTER      => $options[ self::AFTER      ] ?? []    ,
            self::BEFORE     => $options[ self::BEFORE     ] ?? []    ,
            self::DEFAULTS   => $options[ self::DEFAULTS   ] ?? []    ,
            self::EXCLUDE    => $options[ self::EXCLUDE    ] ?? null  ,
            self::FIRST_KEYS => $options[ self::FIRST_KEYS ] ?? []    ,
            self::SORT       => $options[ self::SORT       ] ?? true  ,
            self::REDUCE     => $options[ self::REDUCE     ] ?? false ,
            self::INCLUDE    => $options[ self::INCLUDE    ] ?? null  ,
        ];
    }
}