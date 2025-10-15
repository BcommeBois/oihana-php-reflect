<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the Json Schema types.
 *
 * The 'type' keywords are fundamental to JSON Schema because it specifies the data type that a schema should expect.
 *
 * @see https://json-schema.org/understanding-json-schema/reference/type
 *
 * @package oihana\reflect\traits
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.4
 */
final class JsonSchemaType
{
    use ConstantsTrait ;

    /**
     * Arrays are used for ordered elements. In JSON, each element in an array may be of a different type.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array
     */
    public const string ARRAY = 'array';

    /**
     * The boolean type matches only two special values: true and false.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/boolean
     */
    public const string BOOLEAN = 'boolean';

    /**
     * The integer type is used for integral numbers.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#integer
     */
    public const string INTEGER = 'integer';

    /**
     * When a schema specifies a type of null, it has only one acceptable value: null.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/null
     */
    public const string NULL = 'null';

    /**
     * The number type is used for any numeric type, either integers or floating point numbers.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#number
     */
    public const string NUMBER = 'number';

    /**
     * Objects are the mapping type in JSON. They map "keys" to "values".
     * In JSON, the "keys" must always be strings.
     * Each of these pairs is conventionally referred to as a "property".
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object
     */
    public const string OBJECT = 'object' ;

    /**
     * The string type is used for strings of text. It may contain Unicode characters.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string
     */
    public const string STRING = 'string';

    /**
     * The default type when the type is unknown (non-official).
     */
    public const string UNKNOWN = 'unknown';

}