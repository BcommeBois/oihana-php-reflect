# Hydratation — attributs

[← Retour au sommaire](../README.md) · [← Vue d'ensemble](README.md)

Tous les attributs vivent dans `oihana\reflect\attributes`. Ils sont **déclaratifs** — vous les posez sur les propriétés et l'hydrateur les lit.

## `#[HydrateKey]`

Associe une propriété à une **clé source différente** dans les données d'entrée (ex. charges en snake_case, noms de colonnes de base).

```php
use oihana\reflect\attributes\HydrateKey;

class User
{
    #[HydrateKey( 'user_name' )]
    public string $name = '';
}

new Reflection()->hydrate( [ 'user_name' => 'Bob' ] , User::class )->name; // 'Bob'
```

Vous pouvez déclarer **plusieurs clés de repli** — la première présente dans les données l'emporte (dans l'ordre) :

```php
class User
{
    #[HydrateKey( 'user_name' , 'username' , 'login' )]
    public string $name = '';
}

new Reflection()->hydrate( [ 'username' => 'Bob' ] , User::class )->name; // 'Bob' (repli)
```

## `#[HydrateWith]`

Déclare la (les) **classe(s)** servant à hydrater les éléments d'une propriété `array`. Gère les collections **polymorphes**.

```php
use oihana\reflect\attributes\HydrateWith;

class Address { public string $city = ''; }

class Place
{
    #[HydrateWith( Address::class )]
    public array $locations = [];
}

$place = new Reflection()->hydrate(
    [ 'locations' => [ [ 'city' => 'Paris' ], [ 'city' => 'Lyon' ] ] ],
    Place::class
);
$place->locations[1]->city; // 'Lyon'
```

### Polymorphisme

Quand plusieurs classes sont possibles, la cible de chaque élément est choisie par :

1. une **clé discriminante** dans l'élément — `@type`, `type` ou `atType` — comparée (sans tenir compte de la casse) au nom court ou complet de la classe ;
2. sinon une **estimation par propriétés** (la classe dont les propriétés correspondent le mieux aux clés de l'élément), avec repli sur la première classe déclarée.

```php
class Person       { public string $name = ''; }
class Organization { public string $name = ''; }

class Container
{
    #[HydrateWith( Person::class , Organization::class )]
    public array $members = [];
}

new Reflection()->hydrate(
[
    'members' =>
    [
        [ '@type' => 'Person'       , 'name' => 'Alice' ],
        [ '@type' => 'Organization' , 'name' => 'Acme'  ],
    ]
] , Container::class );
```

> Les tableaux d'**enums backed** sont aussi gérés lorsqu'une seule classe d'enum est fournie :
> `#[HydrateWith( Status::class )] public array $history;` résout chaque élément scalaire via `Status::from()`.

## `#[HydrateAs]`

Force une **classe cible** quand le type de la propriété est ambigu (`object`, `array`, `mixed`, ou une union). Force aussi une conversion `DateTimeInterface` que la règle « le scalaire l'emporte » sauterait sinon (voir [Types → dates](types.md#datetimeinterface)).

```php
use oihana\reflect\attributes\HydrateAs;

class QuantitativeValue { public float $value = 0.0; }

class Offer
{
    #[HydrateAs( QuantitativeValue::class )]
    public null|array|QuantitativeValue $eligibleQuantity = null;
}
```

## Alternative PHPDoc — `@var Type[]`

Au lieu de `#[HydrateWith]` pour une seule classe d'élément, vous pouvez documenter le type de l'élément. **Utilisez le nom pleinement qualifié** (le doc-comment est lu littéralement) :

```php
class Geo
{
    /** @var \App\Model\Address[] */
    public array $locations = [];
}
```

`@var Type[]` et `@var array<Type>` sont tous deux reconnus. Les classes d'éléments peuvent être des objets, des **enums backed** ou des **dates**.

## `#[Transient]` / `#[HydrateIgnore]`

Exclut une **propriété publique** dans les **deux** sens :

- hydratation (entrée) — la valeur n'est jamais lue depuis les données ;
- sérialisation (sortie) — `ReflectionTrait::toArray()` ne l'émet jamais.

Les deux noms sont des **alias équivalents** (`HydrateIgnore` étend `Transient`) ; utilisez celui qui se lit le mieux.

```php
use oihana\reflect\attributes\Transient;
use oihana\reflect\attributes\HydrateIgnore;

class Invoice
{
    public float $subtotal = 0.0;
    public float $tax      = 0.0;

    #[Transient]
    public float $total = 0.0;        // calculé ailleurs

    #[HydrateIgnore]
    public ?string $cachedToken = null;
}

$invoice = new Reflection()->hydrate(
    [ 'subtotal' => 100, 'tax' => 20, 'total' => 999 ],
    Invoice::class
);
$invoice->total; // 0.0 — le 999 entrant a été ignoré
```

Usage typique : propriétés **calculées / dérivées** qui ne doivent ni être alimentées depuis les données ni exposées dans la forme sérialisée.

## Voir aussi

- [Types](types.md) — comment les valeurs typées sont résolues.
- [API Reflection → toArray](../reflection.md) — options de sérialisation.
