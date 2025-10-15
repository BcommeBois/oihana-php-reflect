<?php

namespace oihana\reflect\traits ;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

use function oihana\core\json\getJsonType;
use function oihana\reflect\helpers\getPublicProperties;

/**
 *
 * @package oihana\reflect\traits
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.4
 */
trait JsonSchemaTrait
{
    use ReflectionTrait ;

    /**
     * Generate JSON Schema for a class statically.
     *
     * @param bool $strict If true, additionalProperties is set to false in the schema
     * @return array JSON Schema array representation
     *
     * @throws ReflectionException If reflection fails
     */
    public static function jsonSchema( bool $strict = true ): array
    {
        return self::generateJsonSchema(static::class , $strict ) ;
    }

    /**
     * Generate JSON Schema for the current class.
     *
     * Returns a JSON Schema (draft-07) representation of the object structure,
     * including all public properties from the class, traits, and parent classes.
     *
     * @param bool $strict If true, additionalProperties is set to false in the schema
     *
     * @return array JSON Schema array representation
     *
     * @throws ReflectionException If reflection fails
     *
     * @example
     * ```php
     * use org\schema\Place;
     *
     * $place = new Place();
     * $schema = $place->toJsonSchema();
     *
     * // Save schema to file
     * file_put_contents('place-schema.json', json_encode($schema, JSON_PRETTY_PRINT));
     * ```
     *
     * @example
     * ```php
     * // Generate schema from class (static context)
     * $schema = Place::toJsonSchema();
     * ```
     */
    public function toJsonSchema( bool $strict = true ): array
    {
        return self::generateJsonSchema( $this , $strict ) ;
    }

    /**
     * Validate data against a class schema statically.
     *
     * @param array $data Data to validate
     * @param bool $strict Use strict schema validation
     * @return array ['valid' => bool, 'errors' => array]
     *
     * @throws ReflectionException If reflection fails
     */
    public static function validateWithJsonSchema( array $data, bool $strict = true ): array
    {
        $schema = static::jsonSchema( $strict );
        return self::validateAgainstSchema( $data , $schema ) ;
    }

    /**
     * Validate data against the current class schema.
     *
     * @param array $data Data to validate
     * @param bool $strict Use strict schema validation
     * @return array ['valid' => bool, 'errors' => array]
     *
     * @throws ReflectionException If reflection fails
     *
     * @example
     * ```php
     * use org\schema\Place;
     *
     * $place = new Place();
     * $data = [
     *     'name' => 'Tour Eiffel',
     *     'latitude' => 48.8584,
     *     'invalidProp' => 'test'
     * ];
     *
     * $result = $place->validateData($data);
     * if (!$result['valid']) {
     *     foreach ($result['errors'] as $error) {
     *         echo $error . "\n";
     *     }
     * }
     * ```
     */
    public function validateDataWithJsonSchema(array $data, bool $strict = true): array
    {
        $schema = $this->toJsonSchema( $strict );
        return self::validateAgainstSchema($data, $schema);
    }

    /**
     * Extract the description of the specific property.
     *
     * @param ReflectionProperty $prop
     *
     * @return string|null
     */
    private static function extractShortDescription( ReflectionProperty $prop ): ?string
    {
        $doc = $prop->getDocComment();
        if (!$doc)
        {
            return null ;
        }

        $doc = preg_replace('#^/\*\*#', '', $doc);
        $doc = preg_replace('#\*/$#', '', $doc);

        $lines = explode(PHP_EOL , $doc ) ;
        foreach ( $lines as $line )
        {
            $line = preg_replace('/^\s*\*\s?/', '', trim($line));

            if ( $line === '' || str_starts_with($line, '@') )
            {
                continue;
            }

            return $line ;
        }

        return null ;
    }


    /**
     * Internal method to generate JSON Schema from a class or object.
     *
     * @param string|object $classOrInstance
     * @param bool $strict
     * @return array
     * @throws ReflectionException
     */
    private static function generateJsonSchema( string|object $classOrInstance , bool $strict ): array
    {
        $instance        = is_object( $classOrInstance ) ? $classOrInstance : new static() ;
        $reflection      = $instance->reflection();
        $reflectionClass = $reflection->reflection( $classOrInstance ) ;

        $schema =
        [
            'type'       => 'object',
            'properties' => [] ,
        ];

        if ( $strict )
        {
            $schema[ '$schema'              ] = 'http://json-schema.org/draft-07/schema#' ;
            $schema[ 'title'                ] = $reflectionClass->getShortName();
            $schema[ 'additionalProperties' ] = false;
        }

        // Add description from docblock if available
        $docComment = $reflectionClass->getDocComment() ;
        if ( $docComment && preg_match('/@see\s+(https?:\/\/[^\s]+)/' , $docComment , $matches ) )
        {
            $schema['$id'] = $matches[1] ;
        }

        // Get all public properties including from traits and parent classes
        $properties = getPublicProperties( $reflectionClass ) ;

        foreach ( $properties as $property )
        {
            $propertyName = $property->getName() ;

            // Skip JSON-LD metadata
            if (
                $propertyName === 'CONTEXT' ||
                str_starts_with( $propertyName , 'atContext' ) ||
                str_starts_with( $propertyName , 'atType'    ) ||
                str_starts_with( $propertyName , '__'        )
            )
            {
                continue;
            }

            $schema[ 'properties' ][ $propertyName ] = self::getPropertyJsonSchema( $property, $instance ) ;
        }

        return $schema ;
    }



    /**
     * Generate JSON Schema for a single property.
     *
     * @param ReflectionProperty $property
     * @param object|null $instance Instance to get default value from
     *
     * @return array
     */
    private static function getPropertyJsonSchema
    (
        ReflectionProperty $property ,
        ?object            $instance = null
    )
    :array
    {
        $schema = [] ;

        $description = self::extractShortDescription( $property );
        if ( $description !== null )
        {
            $schema['description'] = $description ;
        }

        // Get default value if property is initialized and instance is provided
        if ( $instance !== null && $property->isInitialized( $instance ) )
        {
            $defaultValue = $property->getValue( $instance ) ;
            if ( $defaultValue !== null )
            {
                $schema['default'] = $defaultValue ;
            }
        }

        // Get type from property
        $type = $property->getType();

        if ($type === null)
        {
            // No type hint - allow any type with oneOf
            $schema[ 'oneOf' ] =
            [
                ['type' => 'string' ] ,
                ['type' => 'number' ] ,
                ['type' => 'boolean'] ,
                ['type' => 'object' ] ,
                ['type' => 'array'  ] ,
                ['type' => 'null'   ]
            ];
            return $schema;
        }

        if ( $type instanceof ReflectionNamedType )
        {
            $mapped = array_merge( $schema , self::mapPhpTypeToJsonSchema($type) ) ;

            if ( $type->allowsNull() && $mapped['type'] !== 'null' ) // If nullable, use oneOf
            {
                $oneOfTypes = [['type' => 'null']];

                if (isset($mapped['type']))
                {
                    if (is_array($mapped['type']))
                    {
                        foreach ($mapped['type'] as $t)
                        {
                            if ( $t !== 'null' ) // Avoid duplicate null type
                            {
                                $oneOfTypes[] = ['type' => $t] ;
                            }
                        }
                    }
                    else
                    {
                        $oneOfTypes[] = ['type' => $mapped['type']]; // For simple nullable types like ?int
                    }
                }

                $schema['oneOf'] = $oneOfTypes;

                if ( isset($mapped['description'] ) )
                {
                    $schema['description'] = $mapped['description'];
                }
            }
            else
            {
                $schema = array_merge( $schema , $mapped ) ;
            }
        }
        else if ( $type instanceof ReflectionUnionType )
        {
            $types   = [] ;
            $hasNull = false ;

            foreach ($type->getTypes() as $unionType)
            {
                $typeName = $unionType->getName();

                if ( $typeName === 'null' )
                {
                    $hasNull = true;
                }
                else
                {
                    $mapped = self::mapPhpTypeToJsonSchema($unionType) ;
                    if (isset( $mapped['type'] ) )
                    {
                        if ( is_array( $mapped['type'] ) )
                        {
                            foreach ( $mapped['type'] as $t )
                            {
                                $types[] = ['type' => $t ] ;
                            }
                        }
                        else
                        {
                            $types[] = [ 'type' => $mapped['type'] ] ;
                        }
                    }
                }
            }

            if ( $hasNull )
            {
                array_unshift( $types , ['type' => 'null'] );
            }

            // Remove duplicates based on type
            $uniqueTypes = [] ;
            $seen        = [] ;

            foreach ( $types as $typeObj )
            {
                $key = $typeObj['type'] ;
                if ( !isset( $seen[$key] ) )
                {
                    $uniqueTypes[] = $typeObj;
                    $seen[$key]    = true;
                }
            }

            if ( count( $uniqueTypes ) > 1 )
            {
                $schema['oneOf'] = $uniqueTypes;
            }
            else if ( count( $uniqueTypes ) === 1 )
            {
                $schema['type'] = $uniqueTypes[0]['type'];
            }
        }

        return $schema ;
    }

    /**
     * Map PHP type to JSON Schema type.
     *
     * @param  ReflectionNamedType $type
     * @return array
     */
    private static function mapPhpTypeToJsonSchema( ReflectionNamedType $type ): array
    {
        $schema   = [] ;
        $typeName = $type->getName() ;

        $mapping =
        [
            'string' => 'string',
            'int'    => 'integer',
            'float'  => 'number',
            'bool'   => 'boolean',
            'array'  => 'array',
            'object' => 'object',
            'null'   => 'null',
            'mixed'  => ['string' , 'number' , 'boolean' , 'object' , 'array' , 'null' ]
        ];

        if ( isset( $mapping[ $typeName] ) )
        {
            $schema['type'] = $mapping[$typeName];
        }
        else if ( class_exists( $typeName ) )
        {
            $shortName = new ReflectionClass( $typeName )->getShortName();
            $schema['type'] = 'object';
            $schema['$ref'] = "#/definitions/$shortName";
            if ( isset($schema['description']) )
            {
                $schema['description'] .= " (Type: $shortName)";
            }
            else
            {
                $schema['description'] = "Type: $shortName";
            }
        }
        else
        {
            $schema['type'] = [ 'string' , 'number' , 'boolean' , 'object' , 'array' , 'null' ] ;
        }

        return $schema;
    }

    /**
     * Validate data against a JSON Schema.
     *
     * @param array $data
     * @param array $schema
     * @return array ['valid' => bool, 'errors' => array]
     */
    private static function validateAgainstSchema( array $data , array $schema ) :array
    {
        $errors = [];

        if ( !isset($schema['properties']) )
        {
            return [ 'valid' => true , 'errors' => [] ] ;
        }

        foreach ($data as $key => $value)
        {
            if ( !isset( $schema['properties'][$key] ) ) // Check if property exists in schema
            {
                if ( isset($schema['additionalProperties']) && $schema['additionalProperties'] === false )
                {
                    $errors[] = "Property '$key' is not defined in schema" ;
                }
                continue ;
            }

            $propertySchema = $schema['properties'][$key];
            $errors         = array_merge( $errors , self::validateValue( $value , $propertySchema , $key ) ) ;
        }

        return
        [
            'valid'  => empty( $errors ) ,
            'errors' => $errors
        ];
    }

    /**
     * Validate a single value against its schema.
     *
     * @param mixed $value
     * @param array $schema
     * @param string $path
     * @return array
     */
    private static function validateValue( mixed $value, array $schema, string $path ) :array
    {
        $errors = [];
        $types  = [];

        if ( isset($schema['type']) )
        {
            $types = is_array($schema['type']) ? $schema['type'] : [$schema['type']];
        }

        if (isset($schema['oneOf']) && is_array($schema['oneOf']))
        {
            foreach ($schema['oneOf'] as $subSchema)
            {
                if (isset( $subSchema['type'] ) )
                {
                    $types[] = $subSchema['type'];
                }
            }
        }

        if ( empty( $types ) )
        {
            return $errors;
        }

        $actual = getJsonType($value);

        if (!in_array( $actual , array_unique($types), true))
        {
            $errors[] = sprintf
            (
                "Property '%s' should be of type [%s], got %s",
                $path,
                implode(',', array_unique($types)),
                $actual
            );
        }

        return $errors;
    }
}