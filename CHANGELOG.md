# Oihana PHP Reflect - OpenSource library - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added

- Enums
  - CallableParameter : the keys of the callable-parameter descriptors returned by Reflection::describeCallableParameters()
  - HydrateDiscriminator : the discriminator keys (`@type`/`type`/`atType`) used to pick a polymorphic target class
  - HydrationPlan : the keys describing the cached per-class hydration plan
  - JsonSchemaDraft : the Json Schema draft versions
  - JsonSchemaFormat : the standard Json Schema string formats (`date-time`, `email`, `uri`, ...)
  - JsonSchemaKeyword : the Json Schema keywords
  - JsonSchemaType : the Json Schema types
  - PhpType : the main PHP types (+ helpers `PhpType::isScalar()` and `PhpType::isNumeric()`)
  - SerializeOption : `toArray()` options specific to this package (`DATE_FORMAT`, `USE_HYDRATE_KEYS`)
- Serialization symmetry
  - ReflectionTrait::toArray() now serializes `DateTimeInterface` property values to a string (ISO 8601 by default, overridable via `SerializeOption::DATE_FORMAT`), and can emit each property under its `#[HydrateKey]` source key when `SerializeOption::USE_HYDRATE_KEYS` is enabled — making `toArray()` symmetric with `hydrate()`.
- Helpers
  - getPublicProperties : Get all public non-static properties from a class, including traits and parent classes, with optional external cache.
  - hasTrait : Check if a class uses a specific trait, including traits from parent classes and nested traits, with optional external cache.
  - useConstantsTrait : Checks if a given class uses the `ConstantsTrait`, either directly or via parent classes.
- Traits
  - JsonSchemaTrait : Providing JSON Schema generation and validation capabilities for classes.
  - JsonSchemaTrait now describes enum-typed properties richly : a backed enum maps to its scalar backing type (`string`/`integer`) plus an `enum` constraint listing the case values, matching what `hydrate()` accepts ; a pure (non-backed) enum lists its case names and is flagged with a `$comment` as not hydratable from a scalar. Nullable enums — and nullable class `$ref`s — now keep their full sub-schema inside `oneOf` instead of collapsing to a bare type.
  - JsonSchemaTrait now maps `DateTimeInterface` properties (`DateTime`, `DateTimeImmutable`, the interface itself) to `{ "type": "string", "format": "date-time" }` instead of an opaque object `$ref`, matching the ISO 8601 string that `hydrate()` parses (nullable dates are wrapped in `oneOf`).
  - JsonSchemaTrait now emits the `items` sub-schema for typed arrays : the element type is resolved like `hydrate()` does (from `#[HydrateWith]`, then from a `@var Type[]` / `@var array<Type>` doc-block) and mapped accordingly (enum, `date-time`, or object `$ref`). A polymorphic `#[HydrateWith(A, B)]` produces an `items.oneOf` of the candidate `$ref`s. Untyped arrays and arrays of scalars stay `{ "type": "array" }` with no `items`.
- Utils
  - CborSerializer tool : cbor serializer helper
  - JsonSerializer tool : json serializer with temporary options.
  - JsonSerializer::decode() : decodes a JSON string into an array, or directly into a hydrated object when a class is given (forces `JSON_THROW_ON_ERROR` — malformed JSON fails loud).
  - CborSerializer::decode() : decodes a CBOR string into an array/value, or directly into a hydrated object when a class is given (completes the CBOR round-trip).
- Hydration
  - `#[HydrateKey]` now accepts several source keys (`#[HydrateKey('user_name', 'username')]`) : during hydration the first key present in the data wins (in declaration order). The single-key form is unchanged; `->key` still exposes the primary key, and the new `->keys` exposes all of them.
  - Reflection::hydrate now resolves backed enums : a scalar value whose target property is a `BackedEnum` is converted to the matching case via `Enum::from()` (throws `ValueError` on an unknown value). Also applies to arrays of enums declared via `#[HydrateWith(Enum::class)]` or `@var Enum[]`. Values already holding an enum instance are kept as-is. Hydrating a pure (non-backed) enum from a scalar throws an `InvalidArgumentException` (a pure enum has no scalar representation — declare a backed enum instead).
  - Reflection::hydrate now resolves `DateTimeInterface` properties : a `string` is parsed as a date (ISO 8601 or any format the constructor understands, throws on an unparsable value) and an `int` is read as a Unix timestamp. The concrete class is preserved (`DateTime` stays mutable, `DateTimeImmutable`/subclasses immutable, the abstract `DateTimeInterface` defaults to `DateTimeImmutable`). In a union that also accepts a builtin scalar (e.g. `string|DateTimeInterface`, `null|string|int`) the raw value is kept as-is, unless `#[HydrateAs(DateTimeImmutable::class)]` explicitly forces the conversion. Values already holding a date instance are kept as-is. Also applies to arrays of dates declared via `@var DateTimeImmutable[]`.
  - Reflection::hydrate now supports classes whose constructor declares required arguments : they are instantiated via `newInstanceWithoutConstructor()` and populated from the data instead of throwing `ArgumentCountError`. A constructor callable with no arguments is still invoked normally (its side effects and defaults are preserved). Declared property defaults still apply ; a required property absent from the data stays uninitialized.
  - Reflection::hydrate now assigns property values through `ReflectionProperty::setValue()` instead of a direct assignment, so `readonly` properties and asymmetric-visibility properties (`public private(set)` / `public protected(set)`, PHP 8.4) are initialized correctly instead of throwing. Scalar type coercion and the public-only contract are preserved.
  - Reflection::hydrate now caches a per-class hydration plan (resolved attributes, `@var` item types, constructor strategy, builtin types) so the data-independent reflection work runs once per class instead of once per object. Behaviour is unchanged; in-memory and bounded by the number of hydrated classes (no eviction needed). Measured ~35% faster when hydrating large batches of nested documents (e.g. ArangoDB result sets).

- Exceptions
  - `HydrationException` (extends `InvalidArgumentException`) : every `Reflection::hydrate()` failure now throws this single catchable type — missing class, non-nullable property set to null, invalid backed-enum value (previously `ValueError`), pure enum from a scalar, non-coercible scalar (previously `TypeError`), unparsable date, etc. It exposes `getClassName()`, `getPropertyName()` and the wrapped original error via `getPrevious()`. Because it extends `InvalidArgumentException`, existing `catch (InvalidArgumentException)` / `catch (Throwable)` code is unaffected.
- Attributes
  - `#[Transient]` and its equivalent alias `#[HydrateIgnore]` : exclude a public property from both hydration (input) and `ReflectionTrait::toArray()` (output). Detection uses `ReflectionAttribute::IS_INSTANCEOF`, so either name triggers the same behaviour. Useful for computed/derived properties.
- Reflection introspection
  - Reflection : `clearCache()` empties the internal caches (cached `ReflectionClass` instances and per-class hydration plans), transparently rebuilt on the next call — useful in tests and long-running workers.
  - Reflection : `hasMethod()`, `hasProperty()`, `propertyType()` and `namespace()` (with `ReflectionTrait` wrappers `hasMethod()`, `hasProperty()`, `getPropertyType()`, `getNamespace()`). `propertyType()` renders union types as `A|B` and intersection types as `A&B`.
  - Reflection : `classAttributes()`, `propertyAttributes()`, `methodAttributes()` return the **instantiated** attributes of a class/property/method, optionally filtered by an attribute class (with `ReflectionTrait` wrappers `getClassAttributes()`, `getPropertyAttributes()`, `getMethodAttributes()`).

### Changed
  - ReflectionTrait : Rename the jsonSerializePublicProperties method in toArray( array $options = [] ) 
  - Reflection::parameterType now renders union/intersection parameter types as `A|B` / `A&B` (instead of failing on `ReflectionUnionType::getName()`), consistent with the new `propertyType()`.
  - Reflection::describeCallableParameters now resolves the callable through the shared `oihana\core\callables\resolveCallable()` helper. An unresolvable callable (e.g. `"Unknown::method"`) now throws `InvalidArgumentException` instead of `ReflectionException` — both were already declared in the method's `@throws`.

### Fixed
  - Reflection::hydrate : the `@var Class[]` / `@var array<Class>` PHPDoc hydration of `array` properties never ran — the parsed item type kept the `[]` / `array<>` wrapper (so `class_exists()` was always false) and the pattern did not accept namespaced or leading-backslash class names. Array elements are now hydrated into the documented class.

## [1.0.3] - 2025-08-24

### Added

- oihana\reflect\enums\CaseEnum
- oihana\reflect\enums\FunctionEnum
- oihana\reflect\trait\FunctionCallTrait

## [1.0.2] - 2025-08-13

### Fixed
- Fix the Version class

## [1.0.1] - 2025-08-13

### Added
- Helpers:
    -  `oihana\reflect\helpers\getFunctionInfo` to returns detailed reflection information about a given function or method.
- Documentation generated with phpDocumentor and published site
- PHPUnit test suite and development tooling

## [1.0.0] - 2025-08-13

### Added
 - Initial public release of `oihana/php-reflect`.
 - Reflection helper `oihana\reflect\Reflection`:
   - List and filter constants, methods, and properties
   - Inspect callable parameters (type, default value, nullable, optional, variadic)
   - Describe any callable via `describeCallableParameters()`
   - Hydration utilities to instantiate and populate objects from associative arrays (recursively)
 - Hydration attributes:
   - `#[oihana\reflect\attributes\HydrateKey]` to rename incoming keys
   - `#[oihana\reflect\attributes\HydrateWith]` to hydrate arrays of objects (including polymorphism via `@type`/`type` or property-guessing)
   - `#[oihana\reflect\attributes\HydrateAs]` to disambiguate `object`/`array`/`mixed`/unions 
 - Traits:
   - `oihana\reflect\traits\ReflectionTrait` with `jsonSerializeFromPublicProperties()` (optional reduction)
   - `oihana\reflect\traits\ConstantsTrait` with helpers `getAll`, `includes`, `enums`, `getConstant`, `validate`
 - Exception type `oihana\reflect\exceptions\ConstantException` for invalid constant operations
 - Value object `oihana\reflect\Version` packing major/minor/build/revision into a 32-bit integer with configurable string output and `fromString()`
 - PSR-4 autoloading, PHP >= 8.4 requirement
 - Documentation generated with phpDocumentor and published site
 - PHPUnit test suite and development tooling


