# Helpers

[← Retour au sommaire](README.md)

Fonctions autonomes du namespace `oihana\reflect\helpers`, autochargées via la clé Composer `files`.

## getFunctionInfo

```php
use function oihana\reflect\helpers\getFunctionInfo;

$info = getFunctionInfo( 'strlen' );
// ou une closure, une chaîne 'Class::method', ou [ $obj, 'method' ]
```

Retourne un tableau associatif décrivant un callable — `name`, `namespace`, `alias` (nom court), `startLine`, etc. — ou `null` s'il n'existe pas.

## getPublicProperties

```php
use function oihana\reflect\helpers\getPublicProperties;

$props = getPublicProperties( new ReflectionClass( User::class ) );
```

Retourne toutes les propriétés **publiques, non statiques** d'une classe, y compris celles héritées des traits et des classes parentes.

- `getPublicProperties( ReflectionClass $class , bool $recursive = true , array &$cache = [] ) : array`
- Passez un tableau `&$cache` externe pour mémoïser les résultats entre les appels.

## hasTrait

```php
use function oihana\reflect\helpers\hasTrait;

hasTrait( new ReflectionClass( User::class ) , ReflectionTrait::class ); // bool
```

Vérifie si une classe utilise un trait donné — y compris les traits apportés par les classes parentes et les traits imbriqués.

- `hasTrait( ReflectionClass $class , string $traitName , bool $recursive = true , array &$cache = [] ) : bool`

## useConstantsTrait

```php
use function oihana\reflect\helpers\useConstantsTrait;

useConstantsTrait( Color::class ); // bool — Color utilise-t-il ConstantsTrait ?
```

Une vérification ciblée pour le [`ConstantsTrait`](constants-trait.md), directement ou via les classes parentes.

- `useConstantsTrait( string $className , array &$cache = [] ) : bool`
