# FunctionCallTrait

[← Retour au sommaire](README.md)

`oihana\reflect\traits\FunctionCallTrait` analyse et manipule des **expressions d'appel de fonction** données sous forme de chaînes, ex. `"sum(1, 2, 3)"`. Utile pour de petits DSL, des pipelines pilotés par configuration, ou des expressions de règles. Les méthodes sont **statiques**.

```php
use oihana\reflect\traits\FunctionCallTrait;

class Expr { use FunctionCallTrait; }
```

## Analyse

```php
Expr::isFunctionCall( 'sum(1, 2)' );   // true
Expr::isFunctionCall( 'not a call' );  // false

Expr::getFunctionName( 'sum(1, 2)' );  // 'sum'
Expr::getArguments( 'sum(1, 2, 3)' );  // ['1', '2', '3']

Expr::splitExpression( 'sum(1, 2)' );  // ['function' => 'sum', 'arguments' => ['1','2']]
```

Les clés du descripteur sont des constantes nommées dans `oihana\reflect\enums\FunctionEnum` (`FUNCTION`, `ARGUMENTS`).

## Validation

```php
Expr::isValidArguments( 'sum(1, 2)' , min: 1 , max: 3 ); // true
Expr::isValidArguments( 'sum()' , min: 1 );              // false
```

## Réécriture

```php
Expr::replaceArguments( 'sum(1, 2)' , [ '10', '20', '30' ] ); // 'sum(10, 20, 30)'
Expr::toCanonicalExpression( 'sum(  1 ,2 )' );                // 'sum(1, 2)'
```

## Normalisation de la casse du nom

Chaque méthode accepte un argument `$case` optionnel pour normaliser le nom de fonction, via `oihana\reflect\enums\CaseEnum` (`LOWER`, `UPPER`) :

```php
use oihana\reflect\enums\CaseEnum;

Expr::getFunctionName( 'SUM(1, 2)' , CaseEnum::LOWER ); // 'sum'
```
