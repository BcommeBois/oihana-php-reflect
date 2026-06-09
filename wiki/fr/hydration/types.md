# Hydratation — types

[← Retour au sommaire](../README.md) · [← Vue d'ensemble](README.md)

L'hydrateur résout chaque valeur selon le **type déclaré** de la propriété. Cette page couvre tous les cas pris en charge.

## Objets imbriqués

Une propriété typée comme une classe est hydratée récursivement depuis un tableau associatif :

```php
class Address { public string $city = ''; }
class User    { public ?Address $address = null; }

new Reflection()->hydrate( [ 'address' => [ 'city' => 'Paris' ] ] , User::class )
    ->address->city; // 'Paris'
```

## Tableaux d'objets

Utilisez [`#[HydrateWith]`](attributes.md#hydratewith) ou un doc-comment `@var Type[]`. Une liste de tableaux associatifs devient une liste d'objets.

## Enums backed

Un scalaire (string ou int) ciblant un **enum backed** est résolu via `Enum::from()` :

```php
enum Status : string { case Active = 'active'; case Inactive = 'inactive'; }

class User { public Status $status = Status::Inactive; }

new Reflection()->hydrate( [ 'status' => 'active' ] , User::class )->status; // Status::Active
```

- Une valeur **inconnue** échoue explicitement (`Enum::from()` lève une erreur, enveloppée dans une [`HydrationException`](errors.md)).
- Une valeur contenant déjà une **instance** d'enum est conservée telle quelle.
- Les tableaux d'enums fonctionnent via `#[HydrateWith(Status::class)]` ou `@var Status[]`.

> Les **enums purs (non-backed)** n'ont pas de représentation scalaire. Hydrater l'un d'eux depuis un scalaire lève une [`HydrationException`](errors.md) — déclarez plutôt un enum **backed**.

## `DateTimeInterface`

Une propriété typée `DateTime`, `DateTimeImmutable` (ou une sous-classe), ou l'interface `DateTimeInterface` est résolue depuis :

- une **chaîne** → analysée comme une date (ISO 8601 ou tout format compris par le constructeur) ;
- un **int** → un timestamp Unix (secondes).

```php
class Article { public ?DateTimeImmutable $createdAt = null; }

new Reflection()->hydrate( [ 'createdAt' => '2024-01-02T03:04:05+00:00' ] , Article::class )
    ->createdAt; // DateTimeImmutable
```

La classe concrète est préservée : `DateTime` reste mutable, `DateTimeImmutable`/sous-classes immuables, et l'interface abstraite `DateTimeInterface` retombe sur `DateTimeImmutable`. Une date déjà construite est conservée. Un timestamp numérique doit être passé en **int** (une *chaîne* numérique est traitée comme une chaîne de date).

### La règle « le scalaire l'emporte »

Si la propriété est une **union acceptant aussi un scalaire natif** (ex. `string|DateTimeInterface`, ou la forme schema.org courante `null|string|int`), le **scalaire brut est conservé** — la valeur n'est *pas* convertie en date :

```php
class Event
{
    public null|string|DateTimeInterface $endDate = null; // chaîne conservée
    public null|string|int               $startDate = null; // jamais convertie
}
```

Pour forcer la conversion dans une telle union, utilisez [`#[HydrateAs(DateTimeImmutable::class)]`](attributes.md#hydrateas). Une propriété typée **strictement** comme une date (ex. `DateTimeImmutable $d`) est toujours convertie.

## Unions & nullabilité

- `?Type` et `Type|null` sont respectés — `null` est affecté quand c'est autorisé.
- Affecter `null` à une propriété **non-nullable** lève une [`HydrationException`](errors.md).
- Pour une union contenant un scalaire natif, le scalaire brut a priorité sur une conversion de date ambiguë (voir la règle ci-dessus).

## Coercition scalaire

Les valeurs scalaires sont converties vers le type déclaré selon le **typage coercitif** de PHP :

| Déclaré | Entrée | Résultat |
|---|---|---|
| `int` | `'42'` | `42` |
| `float` | `'3.14'` | `3.14` |
| `bool` | `'1'` / `'0'` | `true` / `false` |
| `string` | `7` | `'7'` |

Une valeur **non coercible** (ex. `'abc'` → `int`) lève une [`HydrationException`](errors.md). Ce comportement est **indépendant de `strict_types`** (les valeurs sont affectées via `ReflectionProperty::setValue()`).

## Propriétés `readonly` & à visibilité asymétrique

Les propriétés `readonly` et celles à visibilité asymétrique PHP 8.4 (`public private(set)`, `public protected(set)`) sont affectées **par réflexion**, donc correctement initialisées :

```php
class Entity
{
    public readonly string  $id;
    public private(set) int $version;
}

$e = new Reflection()->hydrate( [ 'id' => 'abc', 'version' => 3 ] , Entity::class );
$e->id;      // 'abc'
$e->version; // 3
```

## Classes avec constructeur requis

Si la classe cible déclare des arguments de constructeur **requis**, l'objet est créé avec `newInstanceWithoutConstructor()` puis alimenté depuis les données — fini l'`ArgumentCountError` :

```php
class Money
{
    public int $amount = 0;
    public function __construct( public string $currency ) {}
}

new Reflection()->hydrate( [ 'currency' => 'EUR', 'amount' => 100 ] , Money::class );
```

Un constructeur **appelable sans argument** est toujours exécuté normalement (ses effets de bord et défauts sont préservés). Les valeurs par défaut déclarées s'appliquent toujours ; une propriété requise absente des données reste non initialisée.

## Voir aussi

- [Attributs](attributes.md) · [Erreurs](errors.md) · [Performance](performance.md)
