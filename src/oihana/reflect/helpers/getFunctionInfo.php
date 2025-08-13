<?php

namespace oihana\reflect\helpers;

use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Returns detailed reflection information about a given function or method.
 *
 * This function uses PHP's Reflection API to retrieve metadata about
 * a callable (function, method, or closure), including its namespace,
 * file location, line numbers, and docblock comment.
 *
 * @param callable|string $callable The callable to reflect: function name, closure, or method (as string or array).
 *
 * @return array|null Returns an associative array of function details if the function exists, or null otherwise.
 *                    The array contains:
 *                    - 'name'       : Full function or method name including class if applicable.
 *                    - 'namespace'  : Namespace the function or method belongs to.
 *                    - 'alias'      : Short function or method name without namespace.
 *                    - 'file'       : Path to the file where the function or method is defined.
 *                    - 'startLine'  : The starting line number of the function or method definition.
 *                    - 'endLine'    : The ending line number of the function or method definition.
 *                    - 'isInternal' : Whether the function or method is internal to PHP.
 *                    - 'isUser'     : Whether the function or method is user-defined.
 *                    - 'comment'    : The function's or method's docblock comment, or null if none.
 *
 * @example
 * ```php
 * $info = getFunctionInfo('strlen');
 * print_r($info);
 *
 * $infoMethod = getFunctionInfo([DateTime::class, 'format']);
 * print_r($infoMethod);
 *
 * $closure = function($x) { return $x * 2; };
 * $infoClosure = getFunctionInfo($closure);
 * print_r($infoClosure);
 * ```
 *
 * @package oihana\core\reflections
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getFunctionInfo( callable|string $callable ) : ?array
{
    try
    {
        if ( is_string( $callable ) )
        {
            // Static or instance method as "Class::method"
            if ( str_contains( $callable , '::' ) )
            {
                $parts = explode('::', $callable, 2);
                $ref = new ReflectionMethod( $parts[0] , $parts[1] ) ;
            }
            else
            {
                if ( !function_exists( $callable ) ) // Function name
                {
                    return null;
                }
                $ref = new ReflectionFunction($callable);
            }
        }
        elseif ( is_array( $callable ) && count( $callable ) === 2 )
        {
            $ref = new ReflectionMethod( $callable[0] , $callable[1] ) ; // Method as [object|string, method]
        }
        elseif ( $callable instanceof Closure )
        {
            $ref = new ReflectionFunction( $callable );     // Closure
        }
        else
        {
            return null ; // Unknown type
        }

        $name = $ref->getName();
        $namespace = $ref instanceof ReflectionMethod
            ? $ref->getDeclaringClass()->getNamespaceName()
            : $ref->getNamespaceName();

        return
        [
            'name'       => $ref instanceof ReflectionMethod ? $ref->getDeclaringClass()->getName() . '::' . $ref->getName() : $name,
            'namespace'  => $namespace,
            'alias'      => $ref->getShortName() ,
            'file'       => $ref->getFileName() ,
            'startLine'  => $ref->getStartLine() ,
            'endLine'    => $ref->getEndLine() ,
            'isInternal' => $ref->isInternal() ,
            'isUser'     => $ref->isUserDefined() ,
            'comment'    => $ref->getDocComment() ?: null
        ];
    }
    catch ( ReflectionException )
    {
        return null;
    }
}