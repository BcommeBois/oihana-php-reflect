<?php

namespace oihana\reflect\traits;

use oihana\reflect\enums\CaseEnum;
use oihana\reflect\enums\FunctionEnum;

/**
 * Utility methods for detecting, parsing, validating, and manipulating
 * expressions written in a functional call format, e.g. `APPEND([1,2], 3)`.
 *
 * Depends on ConstantsTrait for retrieving the list of known valid functions.
 *
 * This trait provides:
 * - Extraction of function name and arguments
 * - Validation of argument counts
 * - Case-sensitive or case-insensitive function recognition
 * - Conversion to canonical expression
 * - Replacement of arguments in function calls
 *
 * Case handling:
 * - Optional `$case` parameter in most methods (`'upper'` or `'lower'`)
 *   allows enforcing the desired case for the function name during validation
 *   and extraction.
 *
 * Examples:
 * ```php
 * final class MyFunctions {
 *     use FunctionCallTrait;
 *
 *     public const string APPEND = 'APPEND';
 *     public const string MERGE  = 'MERGE';
 * }
 *
 * $expr = 'append([1,2], 3)';
 *
 * // Basic function name extraction
 * FunctionCallTrait::getFunctionName($expr);            // 'append'
 * FunctionCallTrait::getFunctionName($expr, 'upper');   // 'APPEND'
 * FunctionCallTrait::getFunctionName($expr, 'lower');   // 'append'
 *
 * // Argument extraction
 * FunctionCallTrait::getArguments($expr);              // ['[1,2]', '3']
 * FunctionCallTrait::getArguments($expr, 'upper');     // ['[1,2]', '3'] (case affects validation only)
 *
 * // Validation of function call
 * FunctionCallTrait::isFunctionCall($expr);            // true
 * FunctionCallTrait::isFunctionCall($expr, 'upper');   // true
 * FunctionCallTrait::isFunctionCall('unknown(1)');     // false
 *
 * // Split expression into function and arguments
 * FunctionCallTrait::splitExpression($expr);
 * // [
 * //     'function'  => 'append',
 * //     'arguments' => ['[1,2]', '3']
 * // ]
 *
 * FunctionCallTrait::splitExpression($expr, 'upper');
 * // [
 * //     'function'  => 'APPEND',
 * //     'arguments' => ['[1,2]', '3']
 * // ]
 *
 * // Convert to canonical expression
 * FunctionCallTrait::toCanonicalExpression($expr);           // 'APPEND([1,2], 3)'
 * FunctionCallTrait::toCanonicalExpression($expr, 'lower');  // 'append([1,2], 3)'
 *
 * // Replace arguments in a function call
 * FunctionCallTrait::replaceArguments($expr, ['[4,5]', '6']);
 * // 'APPEND([4,5], 6)'
 *
 * // Check argument counts
 * FunctionCallTrait::isValidArguments($expr, 2);             // true
 * FunctionCallTrait::isValidArguments($expr, 2, 3);          // true
 * FunctionCallTrait::isValidArguments($expr, 3);             // false
 * ```
 *
 * @package oihana\reflect\traits
 * @author  Marc Alcaraz
 * @since   1.0.3
 */
trait FunctionCallTrait
{
    use ConstantsTrait;

    /**
     * Extract the function name from a given expression.
     *
     * @param  string $expression The expression to parse.
     * @param ?string $case       Optional: 'upper' or 'lower' to force case comparison.
     *
     * @return string|null The function name in uppercase, or null if invalid.
     *
     * @example
     * ```php
     * FunctionCallTrait::getFunctionName('APPEND([1,2], 3)'); // "APPEND"
     * FunctionCallTrait::getFunctionName('foo_bar(1)');       // null
     * ```
     */
    public static function getFunctionName( string $expression , ?string $case = null ): ?string
    {
        $expression = trim( $expression ) ;

        if ( !static::isFunctionCall( $expression , $case ) )
        {
            return null;
        }

        $name = strtok( $expression , '(' )  ;

        return match( $case )
        {
            CaseEnum::LOWER => strtolower( $name ) ,
            CaseEnum::UPPER => strtoupper( $name ) ,
            default => $name
        };
    }

    /**
     * Extract the arguments from a function call expression.
     *
     * @param  string $expression The function call expression.
     * @param ?string $case       Optional: 'upper' or 'lower' to force case comparison.
     *
     * @return array|null Returns an array of arguments or null if invalid.
     *
     * @example
     * ```php
     * FunctionCallTrait::getArguments('APPEND([1,2], 3)');
     * // Returns: ['[1,2]', '3']
     *
     * FunctionCallTrait::getArguments('MERGE({a:1}, {b:2})');
     * // Returns: ['{a:1}', '{b:2}']
     * ```
     */
    public static function getArguments( string $expression , ?string $case = null ): ?array
    {
        $expression = trim( $expression ) ;

        if ( !static::isFunctionCall( $expression , $case ) )
        {
            return null;
        }

        $inside = substr( $expression , strpos($expression, '(') + 1, -1 ) ;

        // Split by commas but ignore commas inside brackets, braces, or nested functions
        $args = preg_split('/,(?![^()\[\]{}]*[]\)}])/', $inside);
        $args = array_map('trim', $args);

        return $args ?: [] ;
    }

    /**
     * Check if the number of arguments in a function call is within a valid range.
     *
     * @param string   $expression The expression to check.
     * @param int      $min        Minimum number of required arguments.
     * @param int|null $max        Optional maximum number of allowed arguments.
     * @param ?string  $case       Optional: 'upper' or 'lower' to force case comparison.
     *
     * @return bool True if the argument count is valid, false otherwise.
     *
     * @example
     * ```php
     * FunctionCallTrait::isValidArguments( 'APPEND([1,2], 3)', 2 ) ;       // true
     * FunctionCallTrait::isValidArguments( 'APPEND([1,2], 3)', 3 ) ;       // false
     * FunctionCallTrait::isValidArguments( 'MERGE({a:1}, {b:2})', 1, 2 ) ; // true
     * ```
     */
    public static function isValidArguments( string $expression, int $min = 0, ?int $max = null , ?string $case = null  ): bool
    {
        $args = static::getArguments( $expression , $case ) ;

        if ($args === null)
        {
            return false;
        }

        $count = count( $args ) ;

        return $count >= $min && ($max === null || $count <= $max);
    }

    /**
     * Check if the given expression matches a known function call.
     *
     * @param string  $expression The expression to validate.
     * @param ?string $case       Optional: 'upper' or 'lower' to force case comparison.
     *
     * @return bool True if the expression is a valid function call, false otherwise.
     *
     * @example
     * ```php
     * use oihana\reflect\traits\FunctionCallTrait;
     *
     * FunctionCallTrait::isFunctionCall('APPEND([1,2], 3)'); // true
     * FunctionCallTrait::isFunctionCall('foo_bar(1, 2)');    // false (unknown function)
     * ```
     */
    public static function isFunctionCall( string $expression , ?string $case = null ): bool
    {
        $expression = trim( $expression ) ;

        // Retrieve the list of valid functions from ConstantsTrait
        $functions = static::getConstantKeys();

        // Apply case transformation if requested
        if ( $case !== null )
        {
            $case = strtolower( $case ) ;
            if ($case === CaseEnum::UPPER )
            {
                $functions  = array_map('strtoupper', $functions);
                $expression = strtoupper( $expression ) ;
            }
            else if ( $case === CaseEnum::LOWER )
            {
                $functions  = array_map('strtolower', $functions);
                $expression = strtolower( $expression ) ;
            }
        }

        $pattern   = '/^(' . implode('|', array_map('preg_quote' , $functions ) ) . ')\s*\(/i';

        return (bool) preg_match( $pattern , $expression ) ;
    }

    /**
     * Split an expression into its function name and arguments.
     *
     * @param  string $expression The expression to analyze.
     * @param ?string $case       Optional: 'upper' or 'lower' for function name.
     *
     * @return array|null Returns an associative array or null if invalid.
     *
     * @example ```php
     * FunctionCallTrait::splitExpression('APPEND([1,2], 3)');
     * // Returns:
     * // [
     * //   'function'  => 'APPEND',
     * //   'arguments' => ['[1,2]', '3']
     * // ]
     * ```
     *
     * @see FunctionEnum
     */
    public static function splitExpression( string $expression , ?string $case = null ) : ?array
    {
        $name = static::getFunctionName( $expression , $case ) ;

        if ( $name === null )
        {
            return null;
        }

        return
        [
            FunctionEnum::FUNCTION  => $name,
            FunctionEnum::ARGUMENTS => static::getArguments($expression),
        ];
    }

    /**
     * Convert a function expression into its canonical representation.
     *
     * - Uppercases the function name.
     * - Normalizes spacing between arguments.
     *
     * @param string  $expression The input expression.
     * @param ?string $case       Optional: 'upper' or 'lower' for function name.
     *
     * @return string|null Canonicalized function expression or null if invalid.
     *
     * @example ```php
     * FunctionCallTrait::toCanonicalExpression('append([1,2],3)');
     * // "APPEND([1,2], 3)"
     * ```
     */
    public static function toCanonicalExpression( string $expression , ?string $case = null ): ?string
    {
        $parts = static::splitExpression( $expression , $case ) ;

        if ( $parts === null )
        {
            return null;
        }

        $name = $parts[ 'function' ] ;
        $name = match ( $case )
        {
            CaseEnum::LOWER => strtolower( $name ) ,
            CaseEnum::UPPER => strtoupper( $name ) ,
            default         => $name ,
        };

        return sprintf('%s(%s)', $name, implode(', ', $parts[ FunctionEnum::ARGUMENTS ] ) );
    }

    /**
     * Replace the arguments of a function call and return the new expression.
     *
     * @param string $expression The original function expression.
     * @param array  $newArgs    The new arguments to insert.
     * @return string|null Returns the updated expression or null if invalid.
     *
     * @example ```php
     * FunctionCallTrait::replaceArguments('APPEND([1,2], 3)', ['[4,5]', '6']);
     * // "APPEND([4,5], 6)"
     * ```
     */
    public static function replaceArguments( string $expression , array $newArgs , ?string $case = null ): ?string
    {
        $name = static::getFunctionName( $expression , $case ) ;

        if ( $name === null )
        {
            return null;
        }

        return sprintf('%s(%s)', strtoupper( $name ) , implode(', ', $newArgs ) ) ;
    }
}