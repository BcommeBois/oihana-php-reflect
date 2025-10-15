<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the Json Schema keywords.
 *
 * JSON Schema 2020-12 is a JSON media type for defining the structure of JSON data.
 * JSON Schema is intended to define validation, documentation, hyperlink navigation, and interaction control of JSON data.
 *
 * @see https://www.learnjsonschema.com/2020-12/
 *
 * @package oihana\reflect\traits
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.4
 */
final class JsonSchemaKeyword
{
    use ConstantsTrait ;

    /**
     * The additionalProperties keyword is used to control the handling of extra stuff, that is,
     * properties whose names are not listed in the properties keyword or match any of the regular expressions
     * in the 'patternProperties' keyword.
     *
     * By default any additional properties are allowed.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *     "properties":
     *     {
     *         "number": { "type": "number" },
     *         "street_name": { "type": "string" },
     *         "street_type": { "enum": ["Street", "Avenue", "Boulevard"] }
     *     },
     *     "additionalProperties": false
     * }
     * ```
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *     "properties":
     *     {
     *         "number": { "type": "number" },
     *         "street_name": { "type": "string" },
     *         "street_type": { "enum": ["Street", "Avenue", "Boulevard"] }
     *     },
     *     "additionalProperties": { "type": "string" }
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#additionalproperties
     */
    public const string ADDITIONAL_PROPERTIES = 'additionalProperties' ;

    /**
     * To validate against 'allOf', the given data must be valid against all of the given subschemas.
     *
     * @example
     * ```json
     * {
     *     "allOf":
     *     [
     *         { "type": "string" },
     *         { "maxLength": 5 }
     *     ]
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/combining#allOf
     */
    public const string ALL_OF = 'allOf' ;

    /**
     * A less common way to identify a subschema is to create a named anchor in the schema
     * using the $anchor keyword and using that name in the URI fragment.
     *
     * Anchors must start with a letter followed by any number of letters, digits, -, _, :, or ..
     *
     * @example
     * ```json
     * {
     *     "$id": "https://example.com/schemas/address",
     *     "type": "object",
     *     "properties": {
     *         "street_address": { "$anchor": "street_address", "type": "string" },
     *         "city": { "type": "string" },
     *         "state": { "type": "string" }
     *     },
     *     "required": ["street_address", "city", "state"]
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/structuring#anchor
     */
    public const string ANCHOR = '$anchor' ;

    /**
     * To validate against 'anyOf', the given data must be valid against any (one or more) of the given subschemas.
     *
     * @example
     * ```json
     * {
     *     "anyOf":
     *     [
     *         { "type": "string" , "maxLength": 5 },
     *         { "type": "number" , "minimum": 0 }
     *     ]
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/combining#allOf
     */
    public const string ANY_OF = 'anyOf' ;

    /**
     * The $comment keyword is strictly intended for adding comments to a schema.
     * @example
     * ```json
     * {
     *     "$comment": "Created by John Doe",
     *     "type": "object",
     *     "properties":
     *     {
     *         "country":
     *         {
     *              "$comment": "TODO: add enum of countries"
     *         }
     *     }
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/comments
     */
    public const string COMMENT = '$comment' ;

    /**
     * The const keyword is used to restrict a value to a single value.
     * @example
     * ```json
     * {
     *     "properties":
     *     {
     *         "country":
     *         {
     *               "const": "United States of America"
     *         }
     *     }
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/const
     */
    public const string CONST = 'const' ;

    /**
     * Sometimes we have small subschemas that are only intended for use in the current schema a
     * nd it doesn't make sense to define them as separate schemas.
     *
     * @example
     * ```json
     * {
     *     "$id": "https://example.com/schemas/customer",
     *
     *     "type": "object",
     *     "properties":
     *     {
     *         "first_name": { "$ref": "#/$defs/name" },
     *         "last_name": { "$ref": "#/$defs/name" },
     *         "shipping_address": { "$ref": "/schemas/address" },
     *         "billing_address": { "$ref": "/schemas/address" }
     *     },
     *
     *     "required": ["first_name", "last_name", "shipping_address", "billing_address"],
     *
     *     "$defs":
     *     {
     *          "name": { "type": "string" }
     *     }
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/structuring#defs
     */
    public const string DEFS = '$defs' ;

    /**
     * The default keyword specifies a default value.
     * This value is not used to fill in missing values during the validation process.
     *
     * @example
     * ```json
     * {
     *     "title": "Match anything",
     *     "description": "This is a schema that matches anything.",
     *     "default": "Default value",
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/annotations
     */
    public const string DEFAULT = 'default' ;

    /**
     * The dependentRequired keyword conditionally requires that certain properties
     * must be present if a given property is present in an object.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *
     *     "properties":
     *     {
     *        "name": { "type": "string" },
     *        "credit_card": { "type": "number" },
     *        "billing_address": { "type": "string" }
     *     },
     *
     *     "required": ["name"],
     *
     *     "dependentRequired":
     *     {
     *        "credit_card": ["billing_address"]
     *     }
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/conditionals#dependentRequired
     */
    public const string DEPENDENT_REQUIRED = 'dependentRequired' ;

    /**
     * The dependentSchemas keyword conditionally applies a subschema when a given property is present.
     *
     * This schema is applied in the same way allOf applies schemas.
     * Nothing is merged or extended. Both schemas apply independently.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *     "properties":
     *     {
     *         "name": { "type": "string" },
     *         "credit_card": { "type": "number" }
     *     },
     *     "required": ["name"],
     *     "dependentSchemas":
     *     {
     *         "credit_card":
     *         {
     *             "properties": {
     *                 "billing_address": { "type": "string" }
     *             },
     *             "required": ["billing_address"]
     *         }
     *     }
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/conditionals#dependentSchemas
     */
    public const string DEPENDENT_SCHEMAS = 'dependentSchemas' ;

    /**
     * The 'deprecated' keyword is a boolean that indicates that the instance value the keyword applies
     * to should not be used and may be removed in the future.
     *
     * @example
     * ```json
     * {
     *     "title": "Match anything",
     *     "description": "This is a schema that matches anything.",
     *     "default": "Default value",
     *     "deprecated": true,
     *     "readOnly": true,
     *     "writeOnly": false
     * }
     *
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/annotations
     */
    public const string DEPRECATED = 'deprecated' ;

    /**
     * The description keywords must be strings.
     * A "description" will provide a more lengthy explanation about the purpose of the data described by the schema
     * @example
     * ```json
     * {
     *     "title": "Match anything",
     *     "description": "This is a schema that matches anything.",
     *     "default": "Default value",
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/annotations
     */
    public const string DESCRIPTION = 'description' ;

    /**
     * The 'else' keyword.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *     "properties":
     *     {
     *         "street_address":
     *         {
     *             "type": "string"
     *         },
     *         "country":
     *         {
     *             "default": "United States of America",
     *             "enum": ["United States of America", "Canada"]
     *          }
     *     },
     *     "if":
     *     {
     *         "properties":
     *         {
     *             "country": { "const": "United States of America" }
     *         }
     *     },
     *     "then":
     *     {
     *         "properties":
     *         {
     *              "postal_code": { "pattern": "[0-9]{5}(-[0-9]{4})?" }
     *         }
     *     },
     *     "else":
     *     {
     *         "properties":
     *         {
     *              "postal_code": { "pattern": "[A-Z][0-9][A-Z] [0-9][A-Z][0-9]" }
     *         }
     *     }
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/conditionals#ifthenelse
     */
    public const string ELSE = 'else' ;

    /**
     * The enum keyword is used to restrict a value to a fixed set of values.
     * It must be an array with at least one element, where each element is unique.
     * @example
     * ```json
     * {
     *     "properties":
     *     {
     *         "color":
     *         {
     *             "enum": ["red", "amber", "green"]
     *         }
     *     }
     * }
     * ```
     * https://json-schema.org/understanding-json-schema/reference/enum
     */
    public const string ENUM = 'enum' ;

    /**
     * The 'errors' keyword (unofficial).
     */
    public const string ERRORS = 'errors' ;

    /**
     * The examples keyword is a place to provide an array of examples that validate against the schema.
     * This isn't used for validation, but may help with explaining the effect and purpose of the schema to a reader.
     * @example
     * ```json
     * {
     *     "title": "Match anything",
     *     "description": "This is a schema that matches anything.",
     *     "default": "Default value",
     *     "examples": [
     *        "Anything",
     *        4035
     *      ],
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/annotations
     */
    public const string EXAMPLES = 'examples' ;

    /**
     * The exclusive maximum keyword (x < exclusiveMaximum).
     * @example
     * ```json
     * {
     *     "type" : "number",
     *     "exclusiveMaximum" : 100,
     *     "minimum" : 0
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range
     * @see JsonSchemaType::NUMBER
     */
    public const string EXCLUSIVE_MAXIMUM = 'exclusiveMaximum' ;

    /**
     * The exclusive minimum keyword (x > minimum).
     * @example
     * ```json
     * {
     *     "type" : "number",
     *     "exclusiveMinimum" : 0 ,
     *     "exclusiveMaximum" : 100
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range
     * @see JsonSchemaType::NUMBER
     */
    public const string EXCLUSIVE_MINIMUM = 'exclusiveMinimum' ;

    /**
     * The 'format' keyword conveys semantic information for values that may be difficult
     * or impossible to describe using JSON Schema.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/type#format
     * @see JsonSchemaType::STRING
     */
    public const string FORMAT = 'format' ;

    /**
     * You can set the base URI by using the $id keyword at the root of the schema.
     *
     * The value of $id is a URI-reference without a fragment that resolves against the retrieval-uri.
     * The resulting URI is the base URI for the schema.
     *
     * @example
     * ```json
     * {
     *     "$id": "/schemas/address",
     *     "type": "object",
     *     "properties": {
     *         "street_address": { "type": "string" },
     *         "city": { "type": "string" },
     *         "state": { "type": "string" }
     *     },
     *     "required": ["street_address", "city", "state"]
     * }
     * ```
     *
     * @see http://json-schema.org/understanding-json-schema/structuring#id
     */
    public const string ID = '$id' ;

    /**
     * The 'if' keyword.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *     "properties":
     *     {
     *         "street_address":
     *         {
     *             "type": "string"
     *         },
     *         "country":
     *         {
     *             "default": "United States of America",
     *             "enum": ["United States of America", "Canada"]
     *          }
     *     },
     *     "if":
     *     {
     *         "properties":
     *         {
     *             "country": { "const": "United States of America" }
     *         }
     *     },
     *     "then":
     *     {
     *         "properties":
     *         {
     *              "postal_code": { "pattern": "[0-9]{5}(-[0-9]{4})?" }
     *         }
     *     },
     *     "else":
     *     {
     *         "properties":
     *         {
     *              "postal_code": { "pattern": "[A-Z][0-9][A-Z] [0-9][A-Z][0-9]" }
     *         }
     *     }
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/conditionals#ifthenelse
     */
    public const string IF = 'if' ;

    /**
     * List validation is useful for arrays of arbitrary length where each item matches the same schema.
     *
     * @example
     * ```json
     * {
     *     "type": "array",
     *     "items":
     *     {
     *         "type": "number"
     *     }
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/array#items
     * @see JsonSchemaType::ARRAY
     */
    public const string ITEMS = 'items' ;

    /**
     * The maximum keyword (x ≤ maximum).
     * @example
     * ```json
     * {
     *     "type" : "number",
     *     "minimum" : 0,
     *     "maximum" : 100
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range
     * @see JsonSchemaType::NUMBER
     */
    public const string MAXIMUM = 'maximum' ;

    /**
     * The 'maxItems' keyword.
     *
     * @see JsonSchemaType::ARRAY
     */
    public const string MAX_ITEMS = 'maxItems' ;

    /**
     * The length of a string can be constrained using the minLength and maxLength keywords.
     * For both keywords, the value must be a non-negative number.
     *
     * ```json
     * {
     *     "type": "string",
     *     "minLength": 2,
     *     "maxLength": 3
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#length
     * @see JsonSchemaType::STRING
     */
    public const string MAX_LENGTH = 'maxLength' ;

    /**
     * The number of properties on an object can be restricted using the 'minProperties' and 'maxProperties' keywords.
     *
     * Each of these must be a non-negative integer.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#size
     * @see JsonSchemaType::OBJECT
     */
    public const string MAX_PROPERTIES = 'maxProperties' ;

    /**
     * The minimum keyword (x ≥ minimum).
     * @example
     * ```json
     * {
     *     "type" : "number",
     *     "minimum" : 0,
     *     "exclusiveMaximum" : 100
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#range
     * @see JsonSchemaType::NUMBER
     */
    public const string MINIMUM = 'minimum' ;

    /**
     * The 'minItems' keyword.
     *
     * @see JsonSchemaType::ARRAY
     */
    public const string MIN_ITEMS = 'minItems' ;

    /**
     * The number of properties on an object can be restricted using the 'minProperties' and 'maxProperties' keywords.
     *
     * Each of these must be a non-negative integer.
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#size
     * @see JsonSchemaType::OBJECT
     */
    public const string MIN_PROPERTIES = 'minProperties' ;

    /**
     * The length of a string can be constrained using the minLength and maxLength keywords.
     * For both keywords, the value must be a non-negative number.
     *
     * ```json
     * {
     *     "type": "string",
     *     "minLength": 2,
     *     "maxLength": 3
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#length
     * @see JsonSchemaType::STRING
     */
    public const string MIN_LENGTH = 'minLength' ;

    /**
     * Numbers can be restricted to a multiple of a given number,
     * using the multipleOf keyword. It may be set to any positive number.
     *
     * @example
     * ```json
     * {
     *     "type"       : "number",
     *     "multipleOf" : 10
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/numeric#multiples
     * @see JsonSchemaType::NUMBER
     */
    public const string MULTIPLE_OF = 'multipleOf' ;

    /**
     * The 'not' keyword declares that an instance validates if it doesn't validate against the given subschema.
     *
     * @example
     * ```json
     * { "not": { "type": "string" } }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/combining#not
     */
    public const string NOT = 'not' ;

    /**
     * To validate against oneOf, the given data must be valid against exactly one of the given subschemas.
     *
     * @example
     * ```json
     * {
     *     "oneOf":
     *     [
     *         { "type": "number", "multipleOf": 5 },
     *         { "type": "number", "multipleOf": 3 }
     *     ]
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/combining#oneOff
     */
    public const string ONE_OF = 'oneOf' ;

    /**
     * The pattern keyword is used to restrict a string to a particular regular expression.
     *
     * @example
     * ```json
     * {
     *     "type": "string",
     *     "pattern": "^(\\([0-9]{3}\\))?[0-9]{3}-[0-9]{4}$"
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/string#regexp
     */
    public const string PATTERN = 'pattern' ;

    /**
     * Sometimes you want to say that, given a particular kind of property name,
     * the value should match a particular schema.
     *
     * That's where 'patternProperties' comes in: it maps regular expressions to schemas.
     *
     * If a property name matches the given regular expression,
     * the property value must validate against the corresponding schema.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *     "patternProperties":
     *     {
     *         "^S_": { "type": "string" },
     *         "^I_": { "type": "integer" }
     *     }
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#patternProperties
     */
    public const string PATTERN_PROPERTIES = 'patternProperties' ;

    /**
     * List validation is useful for arrays of arbitrary length where each item matches the same schema.
     * @example
     * ```json
     * {
     *     "type" : "array",
     *     "prefixItems" :
     *     [
     *         { "type" : "number" },
     *         { "type" : "string" },
     *         { "enum" : ["Street", "Avenue", "Boulevard"] },
     *         { "enum" : ["NW", "NE", "SW", "SE"] }
     *     ],
     *     "items" : false
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/array#additionalitems
     */
    public const string PREFIX_ITEMS = 'prefixItems' ;

    /**
     * The properties (key-value pairs) on an object are defined using the properties keyword.
     *
     * The value of properties is an object, where each key is the name of a property and each value
     * is a schema used to validate that property.
     *
     * Any property that doesn't match any of the property names
     * in the properties keyword is ignored by this keyword.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *     "properties":
     *     {
     *         "number": { "type": "number" },
     *         "street_name": { "type": "string" },
     *         "street_type": { "enum": ["Street", "Avenue", "Boulevard"] }
     *      }
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#properties
     */
    public const string PROPERTIES = 'properties' ;

    /**
     * The names of properties can be validated against a schema, irrespective of their values.
     *
     * This can be useful if you don't want to enforce specific properties,
     * but you want to make sure that the names of those properties follow a specific convention.
     *
     * You might, for example, want to enforce that all names are valid ASCII tokens
     * so they can be used as attributes in a particular programming language.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *     "propertyNames":
     *     {
     *          "pattern": "^[A-Za-z_][A-Za-z0-9_]*$"
     *     }
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#propertyNames
     */
    public const string PROPERTY_NAMES = 'propertyNames' ;

    /**
     * The 'readOnly' keywords indicates that a value should not be modified.
     * It could be used to indicate that a PUT request that changes a value would result in a 400 Bad Request response.
     *
     * @example
     * ```json
     * {
     *     "title": "Match anything",
     *     "description": "This is a schema that matches anything.",
     *     "default": "Default value",
     *     "deprecated": true,
     *     "readOnly": true,
     *     "writeOnly": false
     * }
     *
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/annotations
     */
    public const string READ_ONLY = 'readOnly' ;

    /**
     * A schema can reference another schema using the $ref keyword.
     *
     * The value of $ref is a URI-reference that is resolved against the schema's Base URI.
     *
     * @example
     * ```json
     * {
     *     "$id": "https://example.com/schemas/customer",
     *     "type": "object",
     *     "properties": {
     *         "first_name": { "type": "string" },
     *         "last_name": { "type": "string" },
     *         "shipping_address": { "$ref": "https://example.com/schemas/address" },
     *         "billing_address": { "$ref": "/schemas/address" }
     *     },
     *     "required": ["first_name", "last_name", "shipping_address", "billing_address"]
     * }
     * ```
     * @see https://json-schema.org/understanding-json-schema/structuring#dollarref
     */
    public const string REF = '$ref' ;

    /**
     * The required keyword takes an array of zero or more strings. Each of these strings must be unique.
     *
     * @example
     * ```json
     * {
     *    "type": "object",
     *    "properties":
     *    {
     *        "name": { "type": "string" },
     *        "email": { "type": "string" },
     *        "address": { "type": "string" },
     *        "telephone": { "type": "string" }
     *    },
     *    "required": ["name", "email"]
     * }
     * ``
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#required
     */
    public const string REQUIRED = 'required' ;

    /**
     * The 'requiredProperties' keyword.
     */
    public const string REQUIRED_PROPERTIES = 'requiredProperties' ;

    /**
     * The $schema keyword is used to declare which dialect of JSON Schema the schema was written for.
     *
     * @example
     * ```json
     * {
     *     "$schema": "https://json-schema.org/draft/2020-12/schema"
     * }
     * ``
     *
     * @see https://json-schema.org/understanding-json-schema/reference/schema#schema
     */
    public const string SCHEMA = '$schema' ;

    /**
     * The 'then' keyword.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     *     "properties":
     *     {
     *         "street_address":
     *         {
     *             "type": "string"
     *         },
     *         "country":
     *         {
     *             "default": "United States of America",
     *             "enum": ["United States of America", "Canada"]
     *          }
     *     },
     *     "if":
     *     {
     *         "properties":
     *         {
     *             "country": { "const": "United States of America" }
     *         }
     *     },
     *     "then":
     *     {
     *         "properties":
     *         {
     *              "postal_code": { "pattern": "[0-9]{5}(-[0-9]{4})?" }
     *         }
     *     },
     *     "else":
     *     {
     *         "properties":
     *         {
     *              "postal_code": { "pattern": "[A-Z][0-9][A-Z] [0-9][A-Z][0-9]" }
     *         }
     *     }
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/conditionals#ifthenelse
     */
    public const string THEN = 'then' ;

    /**
     * The 'title' keywords must be strings.
     *
     * A "title" will preferably be short and will provide a more lengthy explanation
     * about the purpose of the data described by the schema.
     *
     * @example
     * ```json
     * {
     *     "title": "Match anything",
     *     "description": "This is a schema that matches anything.",
     *     "default": "Default value",
     * }
     *
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/annotations
     */
    public const string TITLE = 'title' ;

    /**
     * The 'type' keyword is fundamental to JSON Schema because it specifies the data type that a schema should expect.
     *
     * @example
     * ```json
     * {
     *     "type": "object",
     * }
     *
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/type
     */
    public const string TYPE = 'type' ;

    /**
     * The 'unevaluatedProperties' keyword is similar to additionalProperties except that
     * it can recognize properties declared in subschemas.
     *
     * So, the example from the previous section can be rewritten without the need to redeclare properties.
     *
     * @example
     * ```json
     * {
     *     "allOf":
     *     [
     *         {
     *             "type": "object",
     *             "properties":
     *              {
     *                 "street_address": { "type": "string" },
     *                 "city": { "type": "string" },
     *                 "state": { "type": "string" }
     *             },
     *             "required": ["street_address", "city", "state"]
     *         }
     *     ],
     *     "properties":
     *     {
     *         "type": { "enum": ["residential", "business"] }
     *     },
     *     "required": ["type"],
     *     "unevaluatedProperties": false
     * }
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/object#unevaluatedproperties
     */
    public const string UNEVALUATED_PROPERTIES = 'unevaluatedProperties' ;

    /**
     * The 'uniqueItems' keyword.
     *
     * @see JsonSchemaType::ARRAY
     */
    public const string UNIQUE_ITEMS = 'uniqueItems' ;

    /**
     * The 'valid' keyword (unofficial).
     */
    public const string VALID = 'valid' ;

    /**
     * The 'writeOnly' keywords indicates that a value may be set, but will remain hidden.
     *
     * In could be used to indicate you can set a value with a PUT request,
     * but it would not be included when retrieving that record with a GET request.
     *
     * @example
     * ```json
     * {
     *     "title": "Match anything",
     *     "description": "This is a schema that matches anything.",
     *     "default": "Default value",
     *     "readOnly": false,
     *     "writeOnly": true
     * }
     *
     * ```
     * @see https://json-schema.org/understanding-json-schema/reference/annotations
     */
    public const string WRITE_ONLY = 'writeOnly' ;
}