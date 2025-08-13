<?php

namespace oihana\reflect;

use oihana\enums\Char;
use oihana\interfaces\Equatable;

/**
 * Represents a software version using four components: major, minor, build, and revision.
 *
 * These components are internally encoded into a single 32-bit integer for compact storage and efficient comparison.
 *
 *  ### Key Features:
 *  - Supports dynamic property access (`$version->major`, `$version->minor`, etc.) through magic methods.
 *  - Efficiently encodes and decodes version components using bitwise operations.
 *  - Customizable string representation via the `separator` and `fields` properties.
 *  - Implements equality checking through the `equals()` method.
 *  - Provides a `fromString()` static method for instantiating versions from formatted strings.
 *
 * ### Usage Example:
 * ```php
 * use oihana\reflect\Version ;
 *
 * $version1 = new Version( 2 , 1 , 1 , 110 ) ;
 * $version2 = new Version( 3 , 1 , 1 , 110 ) ;
 * $version3 = new Version( 1 , 2 , 3 , 4 ) ;
 *
 * $version1->major = 3 ;
 *
 * echo( 'version  : ' . $version1 ) ;
 * echo( 'major    : ' . $version1->major ) ;
 * echo( 'minor    : ' . $version1->minor ) ;
 * echo( 'build    : ' . $version1->build ) ;
 * echo( 'revision : ' . $version1->revision ) ;
 *
 * echo( 'version 1 : ' . $version1->valueOf() ) ;
 * echo( 'version 2 : ' . $version2->valueOf() ) ;
 * echo( 'version 3 : ' . $version3->valueOf() ) ;
 *
 * echo( "equals( 'toto' )    : " . ($version1->equals('toto')    ? 'true' : 'false' )) ;
 * echo( "equals( $version2 ) : " . ($version1->equals($version2) ? 'true' : 'false' )) ;
 * echo( "equals( $version3 ) : " . ($version1->equals($version3) ? 'true' : 'false' )) ;
 * ```
 *
 * @package oihana\reflect
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.0
 */
class Version implements Equatable
{
    /**
     * Creates a new Version instance from individual components.
     *
     * @param int $major    The major version number (4 bits).
     * @param int $minor    The minor version number (4 bits).
     * @param int $build    The build number (8 bits).
     * @param int $revision The revision number (16 bits).
     */
    public function __construct( int $major = 0, int $minor = 0, int $build = 0, int $revision = 0 )
    {
        $this->_value = ( $major << 28 ) | ( $minor << 24 ) | ( $build << 16 ) | $revision ;
    }

    /**
     * Specifies how many version components should be included in the string representation.
     * Values can range from 1 to 4. Defaults to 0, which means automatic trimming of trailing zeroes.
     *
     * @var int
     */
    public int $fields = 0 ;

    /**
     * The string used to separate version components when casting to string.
     *
     * @var string
     */
    public string $separator = Char::DOT ;

    /**
     * Checks whether the current instance is equal to another version.
     *
     * @param mixed $value The value to compare to (typically another Version instance).
     * @return bool True if both instances represent the same version; false otherwise.
     *
     * @example
     * ```php
     * $v1 = new Version( 1,0,0,0 );
     * $v2 = new Version( 1,0,0,0 );
     * echo( json_encode( v1->equals( v2 ) ) ) ; //true
     * ```
     *
     * A cast to Number/int force the valueOf, not ideal but sufficient, and the same for any other operators.
     * But as we keep Equatable for now, then we have no reason to not use it.
     */
    public function equals( mixed $value ) :bool
    {
        if( $value instanceof Version )
        {
            return $this->_value == $value->valueOf() ;
        }

        return false ;
    }

    /**
     * Gets or sets the build component (bits 16–23).
     * @var int
     */
    public int $build
    {
        get => $this->RRR( ( $this->_value & 0x00FF0000 ) , 16 ) ;
        set( int $value )
        {
            $this->_value = ( $this->_value & 0xFF00FFFF ) | ( $value << 16 ) ;
        }
    }

    /**
     * Gets or sets the major version component (stored in the highest 4 bits).
     * @var int
     */
    public int $major
    {
        get => $this->RRR( $this->_value , 28 ) ;
        set( int $value )
        {
            $this->_value = ( $this->_value & 0x0FFFFFFF ) | ( $value << 28 ) ;
        }
    }

    /**
     * Gets or sets the minor version component (bits 24–27).
     * @var int
     */
    public int $minor
    {
        get => $this->RRR( ( $this->_value & 0x0F000000 ) , 24 ) ;
        set( int $value )
        {
            $this->_value = ( $this->_value & 0xF0FFFFFF ) | ( $value << 24 ) ;
        }
    }

    /**
     * Gets or sets the revision component (lowest 16 bits).
     * @var int
     */
    public int $revision
    {
        get => $this->_value & 0x0000FFFF ;
        set( int $value )
        {
            $this->_value = ( $this->_value & 0xFFFF0000 ) | $value;
        }
    }

    /**
     * Instantiates a Version from a formatted version string (e.g., "1.2.3.4").
     *
     * @param string $value     The string to parse.
     * @param string $separator The separator to use (defaults to `.`).
     * @return string|null      A stringified version object or null if parsing fails.
     */
    public static function fromString( string $value , string $separator = Char::DOT ) :?string
    {
        if( $value == null || $value == Char::EMPTY )
        {
            return null ;
        }

        $v = new Version() ;

        if( strpos( $value , $separator ) > -1 )
        {
            $values = explode( $separator , $value ) ;
            $len    = count( $values ) ;

            if( $len > 0 )
            {
                $v->major = (int) $values[0] ;
            }

            if( $len > 1 )
            {
                $v->minor = (int) $values[1] ;
            }

            if( $len > 2 )
            {
                $v->build = (int) $values[2] ;
            }

            if( $len > 3 )
            {
                $v->revision = (int) $values[3] ;
            }
        }
        else
        {
            $vv = (int) $value ;
            if( $vv != 0 )
            {
                $v->major = $vv ;
            }
            else
            {
                $v = null ;
            }
        }

        return (string) $v ;
    }


    /**
     * Returns the string representation of the version, respecting `fields` and `separator`.
     * @return string The stringified version (e.g., "1.2.3").
     */
    public function __toString() :string
    {
        $data = [ $this->major , $this->minor , $this->build , $this->revision ] ;

        if( ( $this->fields > 0 ) && ( $this->fields < 5 ) )
        {
            $data = array_slice( $data , 0 , $this->fields ) ;
        }
        else
        {
            $l = count( $data ) ;
            for( $i = $l-1 ; $i>0 ; $i-- )
            {
                if( $data[$i] == 0 )
                {
                    array_pop( $data ) ;
                }
                else
                {
                    break;
                }
            }
        }

        return implode( $this->separator , $data ) ;
    }

    /**
     * Returns the internal 32-bit integer value representing the version.
     * @return int The packed version number.
     */
    public function valueOf():int
    {
        return $this->_value ;
    }

    /**
     * The internal integer that encodes the four version components.
     *
     * @var int
     */
    private int $_value ;

    /**
     * Bitwise logical right shift (unsigned), emulates `>>>` operator.
     *
     * @param int $a The integer to shift.
     * @param int $b The number of bits to shift.
     * @return int The result of the shift.
     */
    private function RRR( int $a , int $b ) :int
    {
        return (int) ( (float) $a / pow( 2 , $b ) );
    }
}
