<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the standard JSON Schema string formats.
 *
 * The 'format' keyword conveys semantic information for string values; these are the
 * built-in attributes defined by JSON Schema 2020-12.
 *
 * @see https://json-schema.org/understanding-json-schema/reference/string#format
 * @see JsonSchemaKeyword::FORMAT
 *
 * @package oihana\reflect\enums
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.4
 */
final class JsonSchemaFormat
{
    use ConstantsTrait ;

    /**
     * A date in the format YYYY-MM-DD (RFC 3339, full-date).
     */
    public const string DATE = 'date' ;

    /**
     * A date-time in the format YYYY-MM-DDThh:mm:ssZ (RFC 3339, date-time).
     */
    public const string DATE_TIME = 'date-time' ;

    /**
     * A duration as defined by the ISO 8601 ABNF (e.g. P3D).
     */
    public const string DURATION = 'duration' ;

    /**
     * An email address (RFC 5321, section 4.1.2).
     */
    public const string EMAIL = 'email' ;

    /**
     * The host name of a machine (RFC 1123, section 2.1).
     */
    public const string HOSTNAME = 'hostname' ;

    /**
     * The internationalized form of an email address (RFC 6531).
     */
    public const string IDN_EMAIL = 'idn-email' ;

    /**
     * An internationalized host name (RFC 5890, section 2.3.2.3).
     */
    public const string IDN_HOSTNAME = 'idn-hostname' ;

    /**
     * An IPv4 address (RFC 2673, section 3.2, "dotted-quad").
     */
    public const string IPV4 = 'ipv4' ;

    /**
     * An IPv6 address (RFC 2373, section 2.2).
     */
    public const string IPV6 = 'ipv6' ;

    /**
     * An internationalized resource identifier (RFC 3987).
     */
    public const string IRI = 'iri' ;

    /**
     * An internationalized resource identifier reference (RFC 3987).
     */
    public const string IRI_REFERENCE = 'iri-reference' ;

    /**
     * A JSON Pointer (RFC 6901).
     */
    public const string JSON_POINTER = 'json-pointer' ;

    /**
     * A regular expression, as defined by the ECMA 262 dialect.
     */
    public const string REGEX = 'regex' ;

    /**
     * A relative JSON Pointer.
     */
    public const string RELATIVE_JSON_POINTER = 'relative-json-pointer' ;

    /**
     * A time in the format hh:mm:ssZ (RFC 3339, full-time).
     */
    public const string TIME = 'time' ;

    /**
     * A universally unique identifier (RFC 4122).
     */
    public const string UUID = 'uuid' ;

    /**
     * A universal resource identifier (RFC 3986).
     */
    public const string URI = 'uri' ;

    /**
     * A URI Reference (RFC 3986, section 4.1), either a URI or a relative-reference.
     */
    public const string URI_REFERENCE = 'uri-reference' ;

    /**
     * A URI Template (RFC 6570).
     */
    public const string URI_TEMPLATE = 'uri-template' ;
}
