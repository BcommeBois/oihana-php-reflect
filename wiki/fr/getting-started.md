# Démarrage

[← Retour au sommaire](README.md)

## Installation

```bash
composer require oihana/php-reflect
```

Prérequis :

- PHP **8.4+**
- `oihana/php-core` (installé automatiquement)

## Deux points d'entrée

La bibliothèque s'utilise de deux façons complémentaires :

1. **Directement**, via la classe `Reflection` — instanciez-la une fois et réutilisez-la (elle met en cache les `ReflectionClass` et les plans d'hydratation) :

   ```php
   use oihana\reflect\Reflection;

   $reflection = new Reflection();
   ```

2. **Via un trait**, avec `ReflectionTrait` — ajoutez les helpers de réflexion/hydratation/`toArray()` à vos propres classes sans rien instancier :

   ```php
   use oihana\reflect\traits\ReflectionTrait;

   class User
   {
       use ReflectionTrait;
       public string $name = '';
   }
   ```

## Votre première hydratation

`hydrate()` transforme un tableau associatif en objet typé, récursivement :

```php
use oihana\reflect\Reflection;

class Address { public string $city = ''; }

class User
{
    public string   $name = '';
    public ?Address $address = null;
}

$user = new Reflection()->hydrate(
[
    'name'    => 'Alice',
    'address' => [ 'city' => 'Paris' ],
] , User::class );

$user->name;          // 'Alice'
$user->address->city; // 'Paris'
```

L'hydrateur n'affecte que les **propriétés publiques**, fait correspondre les clés du tableau aux noms de propriétés (ou aux alias déclarés avec [`#[HydrateKey]`](hydration/attributes.md)), et résout automatiquement objets imbriqués, tableaux d'objets, enums et dates. Voir la [vue d'ensemble de l'hydratation](hydration/README.md).

## Vos premiers appels de réflexion

```php
use oihana\reflect\Reflection;

$reflection = new Reflection();

$reflection->hasMethod( User::class , 'getName' );      // bool
$reflection->hasProperty( User::class , 'address' );    // true
$reflection->propertyType( User::class , 'address' );   // 'Address' (ou null)
$reflection->namespace( User::class );                  // '' ou le namespace du FQCN
$reflection->shortName( User::class );                  // 'User'
```

Voir l'[API Reflection](reflection.md) pour la surface complète.

## Exposer un objet en tableau

Avec `ReflectionTrait`, `toArray()` sérialise les propriétés publiques initialisées — avec des options de filtrage, d'ordre et de réduction des `null` :

```php
use oihana\core\options\ArrayOption;
use oihana\reflect\traits\ReflectionTrait;

class Product
{
    use ReflectionTrait;
    public string  $name = 'Book';
    public ?string $desc = null;
    public int     $stock = 0;
}

new Product()->toArray( [ ArrayOption::REDUCE => true ] );
// [ 'name' => 'Book', 'stock' => 0 ]  ('desc' null supprimé)
```

## Pour aller plus loin

- [Vue d'ensemble de l'hydratation](hydration/README.md) — le cœur de la bibliothèque.
- [Attributs](hydration/attributes.md) — mapping déclaratif.
- [API Reflection](reflection.md) — helpers d'introspection.
