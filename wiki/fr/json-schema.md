# JSON Schema

[← Retour au sommaire](README.md)

`oihana\reflect\traits\JsonSchemaTrait` génère un **JSON Schema (draft 2020-12)** à partir des propriétés publiques d'une classe, et valide des données contre celui-ci. Il introspecte les types de propriétés, la nullabilité et les doc-comments.

```php
use oihana\reflect\traits\JsonSchemaTrait;

class User
{
    use JsonSchemaTrait;

    /** Le nom complet de l'utilisateur. */
    public string  $name;
    public ?int    $age = null;
    public bool    $active = true;
}
```

## Générer un schéma

```php
User::jsonSchema();          // statique — array (le JSON Schema)
new User()->toJsonSchema();  // instance — même résultat
```

Le schéma généré inclut les types de propriétés, les informations requis/nullable et les descriptions extraites des doc-comments. Passez `strict: false` pour assouplir la génération.

## Valider des données

```php
$errors = User::validateWithJsonSchema( [ 'name' => 'Alice', 'age' => 30 ] );      // statique
$errors = new User()->validateDataWithJsonSchema( [ 'name' => 'Alice' ] );          // instance
```

Ces méthodes retournent la liste des erreurs de validation (vide quand les données sont valides).

## Propriétés typées par un enum

Une propriété typée par un enum PHP est décrite selon ce que `hydrate()` accepte réellement, plutôt que comme un `$ref` objet opaque.

Un **enum backed** est mappé vers son type scalaire de backing, accompagné du mot-clé `enum` listant les valeurs des cas :

```php
enum Status: string { case Active = 'active'; case Inactive = 'inactive'; }
enum Priority: int  { case Low = 1; case High = 10; }

class Task
{
    use JsonSchemaTrait;

    public Status    $status;            // { "type": "string",  "enum": ["active", "inactive"] }
    public ?Priority $priority = null;   // { "oneOf": [ { "type": "null" }, { "type": "integer", "enum": [1, 10] } ] }
}
```

Un **enum pur (non-backed)** n'a aucune représentation scalaire : il ne peut donc pas être hydraté depuis des données. Les noms de ses cas sont tout de même listés à titre documentaire, et un `$comment` signale la limitation :

```php
enum Color { case Red; case Blue; }

// public Color $color;
// {
//     "type": "string",
//     "enum": ["Red", "Blue"],
//     "$comment": "Pure (non-backed) enum: not hydratable from a scalar value."
// }
```

## Propriétés date et heure

Une propriété typée par n'importe quelle implémentation de `DateTimeInterface` (`DateTime`, `DateTimeImmutable`, ou l'interface elle-même) est mappée vers une chaîne portant le format `date-time` — la chaîne ISO 8601 que `hydrate()` sait parser :

```php
use DateTimeImmutable;

class Event
{
    use JsonSchemaTrait;

    public DateTimeImmutable  $createdAt;          // { "type": "string", "format": "date-time" }
    public ?DateTime          $updatedAt = null;   // { "oneOf": [ { "type": "null" }, { "type": "string", "format": "date-time" } ] }
}
```

Cela s'applique aux propriétés date « simples ». Dans une union qui accepte aussi un scalaire (ex. `string|DateTimeInterface`), `hydrate()` conserve la valeur brute telle quelle : aucune contrainte `format` n'est alors émise.

## Tableaux typés

Une propriété `array` dont le type d'élément est connu décrit cet élément via le mot-clé `items`. Le type d'élément est résolu exactement comme `hydrate()` le résout — d'abord depuis un attribut `#[HydrateWith]`, puis depuis un doc-block `@var Type[]` / `@var array<Type>` — et chaque élément est mappé comme une propriété simple (enum, `date-time`, ou `$ref` objet) :

```php
use oihana\reflect\attributes\HydrateWith;

class Catalog
{
    use JsonSchemaTrait;

    #[HydrateWith( Product::class )]
    public array $products;        // { "type": "array", "items": { "type": "object", "$ref": "#/definitions/Product" } }

    /** @var \App\Status[] */
    public array $statuses;        // items: { "type": "string", "enum": [ ... ] }

    /** @var \DateTimeImmutable[] */
    public array $dates;           // items: { "type": "string", "format": "date-time" }
}
```

Un `#[HydrateWith(A::class, B::class)]` polymorphe produit `items: { "oneOf": [ { "$ref": ... }, { "$ref": ... } ] }`. Les tableaux non typés — et les tableaux de scalaires (que `hydrate()` ne touche pas) — restent `{ "type": "array" }` sans `items`.

## Propriétés renommées (`#[HydrateKey]`)

Lorsqu'une propriété déclare une clé source `#[HydrateKey]`, le schéma nomme cette propriété d'après la **clé source primaire** — la clé que `hydrate()` lit dans l'entrée — au lieu du nom PHP. La validation suit : les données doivent utiliser la clé source.

```php
use oihana\reflect\attributes\HydrateKey;

class Account
{
    use JsonSchemaTrait;

    #[HydrateKey( 'user_name' )]
    public string $name;                       // propriété du schéma : "user_name"

    #[HydrateKey( 'created_on', 'createdOn' )]
    public ?string $createdAt = null;          // la clé primaire l'emporte -> "created_on"
}
```

Avec plusieurs clés de repli, la première (la clé primaire) est utilisée dans le schéma.

## Enums associés

Les mots-clés, types et versions de draft sont exposés comme constantes nommées :

- `oihana\reflect\enums\JsonSchemaDraft` — versions de draft ;
- `oihana\reflect\enums\JsonSchemaKeyword` — mots-clés du schéma ;
- `oihana\reflect\enums\JsonSchemaType` — types du schéma ;
- `oihana\reflect\enums\JsonSchemaFormat` — les formats de chaîne standard (ex. `date-time`) ;
- `oihana\reflect\enums\PhpType` — les principaux noms de types PHP.

> Note : le schéma généré reflète ce que `hydrate()` accepte — contraintes `enum` pour les enums backed, formats `date-time` pour les propriétés `DateTimeInterface`, `items` des tableaux typés, et renommage par clé source `#[HydrateKey]`.
