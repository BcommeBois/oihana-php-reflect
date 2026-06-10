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

## Enums associés

Les mots-clés, types et versions de draft sont exposés comme constantes nommées :

- `oihana\reflect\enums\JsonSchemaDraft` — versions de draft ;
- `oihana\reflect\enums\JsonSchemaKeyword` — mots-clés du schéma ;
- `oihana\reflect\enums\JsonSchemaType` — types du schéma ;
- `oihana\reflect\enums\PhpType` — les principaux noms de types PHP.

> Note : le générateur mappe les types de propriétés PHP vers les types JSON Schema, y compris les contraintes `enum` pour les enums backed. La prise en compte des conventions d'hydratation plus riches restantes (renommages `#[HydrateKey]`, formats `date-time`, `items` des tableaux typés) est suivie comme une amélioration future.
