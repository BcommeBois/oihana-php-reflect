<?php

namespace oihana\reflect\traits;

use ReflectionClass;

use oihana\reflect\exceptions\ConstantException;

/**
 * The helper to creates constants enumeration classes.
 *
 * @package oihana\traits\exceptions
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
     * The method returns:
     * - a string with the constant name if exactly one constant matches,
     * - an array of constant names if multiple constants share the same value,
     * - or null if no constant matches the given value.
     *
     * The internal cache is used to optimize repeated lookups.
     *
     * @param string $value The value to search for among the constants.
     * @param string|string[]|null $separator Optional separator(s) to split constant values before matching.
     *     - If null, no splitting is performed.
     *     - If a string, it is used as the delimiter.
     *     - If an array of strings, each separator is applied iteratively.
     *
     * @return string|string[]|null The constant name(s) matching the value, or null if none found.
     */
    public static function getConstant( string $value , array|string|null $separator = null ): string|array|null
    {
        if( static::$CONSTANTS === null )
        {
            static::$CONSTANTS = [] ;
        }

        if( is_array( $separator ) )
        {
            sort($separator ) ;
            $key = implode('|' , $separator ) ;
        }
        else
        {
            $key = $separator ?? '__null__';
        }

        if( !isset( static::$CONSTANTS[ $key ] ) )
        {
            static::$CONSTANTS[ $key ] = [] ;

            $all = static::getAll() ;
            foreach ( $all as $name => $constantValue )
            {
                // Explode value by each separator if applicable
                if( $separator !== null )
                {
                    $values = [ $constantValue ] ;
                    if( is_string( $constantValue ) )
                    {
                        if( is_array($separator) )
                        {
                            foreach ($separator as $sep)
                            {
                                $tmp = [];
                                foreach ($values as $val)
                                {
                                    if( str_contains($val, $sep) )
                                    {
                                        $tmp = array_merge($tmp, explode($sep, $val));
                                    }
                                    else
                                    {
                                        $tmp[] = $val;
                                    }
                                }
                                $values = $tmp;
                            }
                        }
                        else
                        {
                            if( str_contains($constantValue, $separator) )
                            {
                                $values = explode($separator, $constantValue);
                            }
                        }
                    }
                    elseif( is_array($constantValue) )
                    {
                        $values = $constantValue;
                    }
                }
                else
                {
                    $values = is_array( $constantValue ) ? $constantValue : [ $constantValue ] ;
                }

                foreach ( $values as $v )
                {
                    if ( !isset(static::$CONSTANTS[$key][$v] ) )
                    {
                        static::$CONSTANTS[$key][$v] = [] ;
                    }
                    static::$CONSTANTS[$key][$v][] = $name;
                }
            }
        }

        if (!isset(static::$CONSTANTS[$key][$value]))
        {
            return null;
        }

        $result = static::$CONSTANTS[$key][$value] ;

        if (count($result) === 1)
        {
            return $result[0] ;
        }

        return $result ;
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