# oihana/php-reflect — Réflexion & hydratation pour PHP

![Langue](https://img.shields.io/badge/langue-Français-blue)

`oihana/php-reflect` est une bibliothèque PHP compacte et ciblée qui fournit :

- une surcouche conviviale à l'API Reflection de PHP (`Reflection`, `ReflectionTrait`) ;
- un **hydrateur tableau → objet** robuste, avec mapping par attributs, résolution des enums & dates, et un cache de plan par classe ;
- des helpers de constantes « façon enum » (`ConstantsTrait`), un générateur de JSON Schema (`JsonSchemaTrait`), des sérialiseurs JSON/CBOR, et un objet-valeur `Version` compact.

![Oihana PHP Reflect](https://raw.githubusercontent.com/BcommeBois/oihana-php-reflect/main/assets/images/oihana-php-reflect-logo-inline-512x160.png)

## À qui s'adresse cette documentation

Aux développeurs PHP qui veulent :

- transformer des tableaux associatifs (charges JSON, lignes de base de données) en **objets typés** — récursivement, avec enums, dates, unions, propriétés `readonly` et constructeurs requis gérés pour vous ;
- mapper des noms de clés externes et des collections polymorphes de façon déclarative, via des attributs, **sans magic strings** ;
- introspecter classes, méthodes, propriétés et callables via une API concise et mise en cache ;
- exposer des propriétés publiques en tableaux / JSON, valider des données contre un JSON Schema généré, ou manipuler des constantes de classe comme des énumérations.

## Démarrage rapide

```php
use oihana\reflect\Reflection;
use oihana\reflect\attributes\HydrateKey;

enum Status : string { case Active = 'active'; case Inactive = 'inactive'; }

class User
{
    #[HydrateKey( 'user_name' )]
    public string $name = '';
    public Status $status = Status::Inactive;
    public ?DateTimeImmutable $createdAt = null;
}

$user = new Reflection()->hydrate(
[
    'user_name' => 'Alice',
    'status'    => 'active',
    'createdAt' => '2024-01-02T03:04:05+00:00',
] , User::class );

$user->name;             // 'Alice'
$user->status;           // Status::Active
$user->createdAt;        // DateTimeImmutable
```

> PHP 8.4 permet d'appeler une méthode directement sur une expression `new` — `new Reflection()->hydrate(...)` — sans parenthèses englobantes. Tous les exemples de ce wiki utilisent cette syntaxe.

## Sommaire

### Démarrage
- [Démarrage](getting-started.md) — installation, prérequis, premières hydratations et appels de réflexion.

### Hydratation (le cœur)
- [Vue d'ensemble](hydration/README.md) — ce que fait l'hydrateur et comment il résout les valeurs.
- [Attributs](hydration/attributes.md) — `#[HydrateKey]`, `#[HydrateWith]`, `#[HydrateAs]`, `#[Transient]` / `#[HydrateIgnore]`.
- [Types](hydration/types.md) — enums, dates, unions & nullabilité, coercition scalaire, `readonly` & constructeurs requis.
- [Erreurs](hydration/errors.md) — `HydrationException` et la gestion des enregistrements invalides.
- [Performance](hydration/performance.md) — le cache de plan d'hydratation par classe.

### Réflexion & utilitaires
- [API Reflection](reflection.md) — constantes, méthodes, propriétés, paramètres et introspection classe/propriété.
- [ConstantsTrait](constants-trait.md) — traiter les constantes de classe comme des énumérations.
- [FunctionCallTrait](function-call-trait.md) — analyser et manipuler des expressions d'appel de fonction.
- [JSON Schema](json-schema.md) — générer et valider un JSON Schema depuis une classe.
- [Sérialisation](serialization.md) — `JsonSerializer`, `CborSerializer`, `SerializationContext`.
- [Version](version.md) — l'objet-valeur `Version`.
- [Helpers](helpers.md) — `getFunctionInfo`, `getPublicProperties`, `hasTrait`, `useConstantsTrait`.

## Prérequis

- PHP **8.4+**
- `oihana/php-core`

## Licence

[MPL-2.0](../../LICENSE) — © Marc Alcaraz (ekameleon).
