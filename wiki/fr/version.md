# Version

[← Retour au sommaire](README.md)

`oihana\reflect\Version` est un objet-valeur compact qui empaquette quatre composants — **major**, **minor**, **build**, **revision** — dans un seul entier 32 bits, avec des getters/setters exposant chaque partie.

```php
use oihana\reflect\Version;

$v = new Version( 1 , 2 , 3 , 4 );

$v->major;    // 1
$v->minor;    // 2
$v->build;    // 3
$v->revision; // 4
```

## Formes chaîne & entier

```php
(string) $v;        // '1.2.3.4'  (sortie chaîne configurable)
$v->valueOf();      // l'entier 32 bits empaqueté
```

## Analyse

`fromString()` analyse une chaîne de version formatée et retourne sa forme **chaîne normalisée** (ou `null` en cas d'échec d'analyse) :

```php
Version::fromString( '1.2.3.4' );        // '1.2.3.4'
Version::fromString( '1-2-3' , '-' );    // analysé avec un séparateur personnalisé
Version::fromString( '' );               // null
```

## Comparaison

```php
$v->equals( new Version( 1 , 2 , 3 , 4 ) ); // true
```

`equals()` compare à une autre `Version` (par valeur empaquetée).
