# FunctionCallTrait

[← Back to index](README.md)

`oihana\reflect\traits\FunctionCallTrait` parses and manipulates **function-call expressions** given as strings, e.g. `"sum(1, 2, 3)"`. Useful for small DSLs, config-driven pipelines, or rule expressions. Methods are **static**.

```php
use oihana\reflect\traits\FunctionCallTrait;

class Expr { use FunctionCallTrait; }
```

## Parsing

```php
Expr::isFunctionCall( 'sum(1, 2)' );   // true
Expr::isFunctionCall( 'not a call' );  // false

Expr::getFunctionName( 'sum(1, 2)' );  // 'sum'
Expr::getArguments( 'sum(1, 2, 3)' );  // ['1', '2', '3']

Expr::splitExpression( 'sum(1, 2)' );  // ['function' => 'sum', 'arguments' => ['1','2']]
```

The descriptor keys are named constants in `oihana\reflect\enums\FunctionEnum` (`FUNCTION`, `ARGUMENTS`).

## Validation

```php
Expr::isValidArguments( 'sum(1, 2)' , min: 1 , max: 3 ); // true
Expr::isValidArguments( 'sum()' , min: 1 );              // false
```

## Rewriting

```php
Expr::replaceArguments( 'sum(1, 2)' , [ '10', '20', '30' ] ); // 'sum(10, 20, 30)'
Expr::toCanonicalExpression( 'sum(  1 ,2 )' );                // 'sum(1, 2)'
```

## Name case normalization

Every method accepts an optional `$case` argument to normalize the function name, using `oihana\reflect\enums\CaseEnum` (`LOWER`, `UPPER`):

```php
use oihana\reflect\enums\CaseEnum;

Expr::getFunctionName( 'SUM(1, 2)' , CaseEnum::LOWER ); // 'sum'
```
