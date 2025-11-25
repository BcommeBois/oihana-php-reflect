<?php

namespace oihana\reflect\traits;

use ReflectionClass;

use oihana\reflect\exceptions\ConstantException;
use function oihana\core\arrays\toArray;

/**
 * Enum-like utilities for classes that expose a set of public constants.
 *
 * Responsibilities:
 * - Extract all constants of the using class with caching ({@see getAll()})
 * - Provide a flattened, unique, sorted list of values ({@see enums()})
 * - Reverse lookup constant name(s) from a value ({@see getConstant()})
 * - Validate membership ({@see validate()}) and perform inclusion checks ({@see includes()})
 * - Reset internal caches when needed ({@see resetCaches()})
 *
 * Behavior and notes:
 * - Supports constants whose values are scalars or arrays
 * - If a constant value is a string that encodes multiple values (e.g. "one,two,three"),
 *   {@see getConstant()} and {@see includes()} can split the string using a separator
 *   or an array of separators, enabling multi-token lookups
 * - Reverse lookup cache is keyed by the separator configuration for performance
 *
 * Typical usage:
 * ```php
 * final class Status
 * {
 *     use ConstantsTrait;
 *     public const string OPEN  = 'open';
 *     public const string CLOSE = 'close';
 * }
 *
 * Status::includes('open');    // true
 * Status::getConstant('open'); // 'OPEN'
 * Status::enums();             // ['close','open'] (sorted)
 * ```
 *
 * Caching:
 * - {@see $ALL} holds the raw constants map (name => value)
 * - {@see $CONSTANTS} holds reverse lookup tables by separator key
 *
 * @see ConstantException Thrown by {@see validate()} when the value is not part of the enum
 * @see ReflectionClass Used internally to extract class constants
 *
 * @package oihana\reflect\traits
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.0
 */
trait ConstantsTrait
{
    /**
     * Returns an array of all constants in this enumeration.
     * @param int $flags The optional second parameter flags may be used to modify the comparison behavior using these values:
     * Comparison type flags:
     * <ul>
     * <li>SORT_REGULAR - compare items normally (don't change types)</li>
     * <li>SORT_NUMERIC - compare items numerically</li>
     * <li>SORT_STRING - compare items as strings</li>
     * <li>SORT_LOCALE_STRING - compare items as strings, based on the current locale.</li>
     * </ul>
     * @return array
     */
    public static function enums( int $flags = SORT_STRING ): array
    {
        $enums  = [] ;
        $values = static::getAll() ;
        foreach ( $values as $value )
        {
            if( is_array( $value ) )
            {
                foreach ( $value as $value2 )
                {
                    $enums[] = $value2 ;
                }
            }
            else
            {
                $enums[] = $value ;
            }
        }
        $enums = array_unique( $enums ) ;
        sort( $enums , $flags ) ;
        return $enums ;
    }

    /**
     * Returns a valid enumeration value or the default value.
     * @param mixed $value
     * @param mixed|null $default
     * @return mixed
     */
    public static function get( mixed $value , mixed $default = null ): mixed
    {
        return static::includes( $value ) ? $value : $default ;
    }

    /**
     * Returns an array of constants in this class.
     * @return array<string, string>
     */
    public static function getAll(): array
    {
        if( is_null( static::$ALL ) )
        {
            static::$ALL = new ReflectionClass( static::class )->getConstants() ;
        }
        return static::$ALL ;
    }

    /**
     * Returns the constant name(s) associated with the given value.
     *
     * This method searches the class constants for one or multiple constants
     * whose value matches (or contains) the provided value.
     *
     * If the constant values are strings containing multiple parts separated
     * by one or more separators, it splits them accordingly before matching.
     *
     * It also supports case-insensitive lookup if the `$caseInsensitive` flag is set.
     *
     * The method returns:
     * - a string with the constant name if exactly one constant matches,
     * - an array of constant names if multiple constants share the same value,
     * - or null if no constant matches the given value.
     *
     * The internal cache is used to optimize repeated lookups.
     *
     * @param string               $value           The value to search for among the constants.
     * @param string|string[]|null $separator       Optional separator(s) to split constant values before matching.
     *                                                - If null, no splitting is performed.
     *                                                - If a string, it is used as the delimiter.
     *                                                - If an array of strings, each separator is applied iteratively.
     * @param bool                 $caseInsensitive If true, performs a case-insensitive search.
     *
     * @return string|string[]|null The constant name(s) matching the value, or null if none found.
     *
     * @example
     * ```php
     * use oihana\reflect\traits\ConstantsTrait ;
     *
     * final class Status
     * {
     *     use ConstantsTrait;
     *
     *     public const string OPEN   = 'open';
     *     public const string CLOSE  = 'close';
     *     public const string MIXED  = 'draft,open,closed';
     * }
     *
     * // Basic lookup without separator
     * echo Status::getConstant('open'); // 'OPEN'
     *
     * // Lookup when multiple constants share the same value
     *
     * final class Codes
     * {
     *     use ConstantsTrait;
     *
     *     public const int OK      = 200;
     *     public const int ALSO_OK = 200;
     *     public const int FAIL    = 500;
     * }
     *
     * var_dump( Codes::getConstant( 200 ) ) ; // ['OK', 'ALSO_OK']
     *
     * // Using separator to match sub-values inside a string constant
     * echo Status::getConstant('draft', ',');
     * // 'MIXED'
     *
     * // Using multiple separators
     * final class Multi
     * {
     *     use ConstantsTrait;
     *     public const string ALPHA = 'a|b,c';
     * }
     * echo Multi::getConstant('b', [',', '|']);
     * // 'ALPHA'
     *
     * // Case-insensitive search
     * echo Status::getConstant('OPEN', null, true); // 'OPEN'
     * echo Status::getConstant('OpEn', null, true); // 'OPEN'
     * ```
     */
    public static function getConstant
    (
        string            $value ,
        array|string|null $separator       = null ,
        bool              $caseInsensitive = false
    )
    : string|array|null
    {
        if( static::$CONSTANTS === null )
        {
            static::$CONSTANTS = [] ;
        }

        if ( is_array( $separator ) )
        {
            sort( $separator ) ;
            $sepKey = implode( '|' , $separator ) ;
        }
        else
        {
            $sepKey = $separator ?? '__null__' ;
        }

        $cacheKey = ( $caseInsensitive ? 'ci|' : '' ) . $sepKey ;

        if( !isset( static::$CONSTANTS[ $cacheKey ] ) )
        {
            static::$CONSTANTS[ $cacheKey ] = [] ;

            $all = static::getAll() ;

            foreach ( $all as $name => $constantValue )
            {
                $values = toArray( $constantValue ) ;

                if ( $separator !== null && is_string( $constantValue ) )
                {
                    if ( is_array( $separator ) )
                    {
                        foreach ( $separator as $sep )
                        {
                            $tmp = [] ;
                            foreach ($values as $val)
                            {
                                if ( str_contains( $val , $sep ) )
                                {
                                    $tmp = array_merge( $tmp , explode( $sep , $val ) ) ;
                                }
                                else
                                {
                                    $tmp[] = $val ;
                                }
                            }
                            $values = $tmp ;
                        }
                    }
                    else
                    {
                        if ( str_contains( $constantValue , $separator ) )
                        {
                            $values = explode( $separator , $constantValue ) ;
                        }
                    }
                }

                foreach ( $values as $v )
                {
                    $vKey = $caseInsensitive ? strtolower( $v ) : $v ;
                    if ( !isset( static::$CONSTANTS[ $cacheKey ][ $vKey ] ) )
                    {
                        static::$CONSTANTS[ $cacheKey ][ $vKey ] = [] ;
                    }
                    static::$CONSTANTS[ $cacheKey ][ $vKey ][] = $name ;
                }
            }
        }

        $searchValue = $caseInsensitive ? strtolower( $value ) : $value ;

        if ( !isset( static::$CONSTANTS[ $cacheKey ][ $searchValue ] ) )
        {
            return null ;
        }

        $result = static::$CONSTANTS[ $cacheKey ][ $searchValue ] ;

        return count($result) === 1 ? $result[0] : $result ;
    }

    /**
     * Returns all constant names (keys) in this class.
     *
     * @return string[]
     *
     * @example
     * ```php
     * final class Status
     * {
     *     use ConstantsTrait;
     *     public const OPEN  = 'open';
     *     public const CLOSE = 'close';
     * }
     * print_r(Status::getConstantKeys());
     * // ['OPEN','CLOSE']
     * ```
     */
    public static function getConstantKeys(): array
    {
        return array_keys( static::getAll() ) ;
    }

    /**
     * Returns all constant values in this class.
     *
     * @return array
     *
     * @example
     * ```php
     * final class Status
     * {
     *     use ConstantsTrait;
     *     public const OPEN  = 'open';
     *     public const CLOSE = 'close';
     * }
     * print_r(Status::getConstantValues());
     * // ['open','close']
     * ```
     */
    public static function getConstantValues(): array
    {
        return array_values( static::getAll() ) ;
    }

    /**
     * Checks if a given value is valid (exists as a constant in this class).
     * @param mixed $value
     * @param bool $strict [optional] <p>
     * If the third parameter strict is set to true
     * then the in_array function will also check the
     * types of the needle in the haystack.
     * </p>
     * @param ?string $separator The optional string separator if the constant value contains multiple values in a single string expression.
     * @return bool True if the value exist, False otherwise.
     */
    public static function includes( mixed $value , bool $strict = false , ?string $separator = null ): bool
    {
        $values = self::getAll() ;
        foreach ( $values as $current )
        {
            if( $value === $current )
            {
                return true ;
            }

            if( isset( $separator ) && is_string( $current ) && str_contains( $current , $separator ) )
            {
                $current = explode( $separator , $current ) ;
            }

            if( is_array( $current ) )
            {
                if ( in_array( $value , $current , $strict ) )
                {
                    return true;
                }
            }
        }
        return false ;
    }

    /**
     * Reset the internal cache of the static methods.
     * @return void
     */
    public static function resetCaches(): void
    {
        static::$ALL       = null ;
        static::$CONSTANTS = null ;
    }

    /**
     * Validates if the passed-in value is a valid element in the current enum.
     * @param mixed $value
     * @param bool $strict [optional] <p>
     * If the third parameter strict is set to true then the in_array function will also check the
     * types of the needle in the haystack.
     * </p>
     * @param ?string $separator The optional string separator if the constant value contains multiple values in a single string expression.
     * @return void
     * @throws ConstantException Thrown when the passed-in value is not a valid constant.
     */
    public static function validate( mixed $value , bool $strict = true , ?string $separator = null ) : void
    {
        if( !static::includes( $value , $strict , $separator ) )
        {
            throw new ConstantException( 'Invalid constant : ' . json_encode( $value , JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) ;
        }
    }

    /**
     * The list of all constants.
     * @var array|null
     */
    protected static ?array $ALL = null ;

    /**
     * The flipped list of all constants.
     * @var array|null
     */
    protected static ?array $CONSTANTS = null ;
}