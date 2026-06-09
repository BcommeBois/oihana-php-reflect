<?php

namespace oihana\reflect\exceptions ;

use InvalidArgumentException ;
use Throwable ;

/**
 * Exception thrown when {@see \oihana\reflect\Reflection::hydrate()} fails.
 *
 * It unifies every hydration failure under a single catchable type: a missing class, a
 * non-nullable property receiving null, an invalid backed-enum value, a pure-enum hydrated
 * from a scalar, a non-coercible scalar, an unparsable date, etc.
 *
 * It extends {@see InvalidArgumentException} so existing `catch (InvalidArgumentException)`
 * code keeps working. When wrapping a lower-level error (e.g. `ValueError`, `TypeError`),
 * the original throwable is available via {@see Throwable::getPrevious()}.
 *
 * @package oihana\reflect\exceptions
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.5
 *
 * @example
 * ```php
 * try
 * {
 *     new Reflection()->hydrate( $row , Product::class ) ;
 * }
 * catch ( HydrationException $e )
 * {
 *     // $e->getClassName(), $e->getPropertyName(), $e->getPrevious()
 *     $logger->warning( "Skipped invalid document: " . $e->getMessage() ) ;
 * }
 * ```
 */
class HydrationException extends InvalidArgumentException
{
    /**
     * @param string         $message      The human-readable error message.
     * @param string|null    $className    The fully-qualified class being hydrated, if known.
     * @param string|null    $propertyName The property that failed, if applicable.
     * @param Throwable|null $previous     The underlying error being wrapped, if any.
     */
    public function __construct
    (
        string $message ,
        private readonly ?string $className = null ,
        private readonly ?string $propertyName = null ,
        ?Throwable $previous = null
    )
    {
        parent::__construct( $message , 0 , $previous ) ;
    }

    /**
     * The fully-qualified class being hydrated when the failure occurred (or null).
     */
    public function getClassName(): ?string
    {
        return $this->className ;
    }

    /**
     * The property that failed to hydrate (or null when not property-specific).
     */
    public function getPropertyName(): ?string
    {
        return $this->propertyName ;
    }
}
