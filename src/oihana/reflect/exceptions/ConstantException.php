<?php

namespace oihana\reflect\exceptions ;

use Exception ;

/**
 * Exception thrown when a class or enum constant fails validation.
 *
 * This exception is typically used within the Reflection utilities to signal that a retrieved constant value is invalid,
 * unexpected, or does not meet required constraints.
 *
 * @package oihana\reflect\exceptions
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.0
 */
class ConstantException extends Exception
{

}