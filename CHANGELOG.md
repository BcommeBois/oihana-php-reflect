# Oihana PHP Reflect - OpenSource library - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

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


