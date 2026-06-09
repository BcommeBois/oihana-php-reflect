# Hydratation — vue d'ensemble

[← Retour au sommaire](../README.md)

L'hydratation transforme un **tableau associatif** (charge JSON, ligne de base de données, bloc de configuration) en un **graphe d'objets typés**. C'est la fonctionnalité centrale d'`oihana/php-reflect`.

```php
use oihana\reflect\Reflection;

$object = new Reflection()->hydrate( $data , MyClass::class );
```

Vous pouvez aussi l'utiliser via [`ReflectionTrait`](../reflection.md) :

```php
class MyClass
{
    use \oihana\reflect\traits\ReflectionTrait;
}

$object = new MyClass()->hydrate( $data , MyClass::class );
```

## Ce qu'il fait

Pour chaque **propriété publique** de la classe cible, l'hydrateur :

1. résout la **clé source** dans les données — le nom de la propriété, ou un alias déclaré avec [`#[HydrateKey]`](attributes.md#hydratekey) ;
2. saute la propriété si la clé est absente (la valeur par défaut déclarée est conservée), ou si elle est marquée [`#[Transient]` / `#[HydrateIgnore]`](attributes.md#transient--hydrateignore) ;
3. résout la valeur selon le **type** déclaré (voir [Types](types.md)) :
   - objet imbriqué → `hydrate()` récursif ;
   - tableau d'objets → via [`#[HydrateWith]`](attributes.md#hydratewith) ou un doc-comment `@var Type[]` ;
   - enum backed → `Enum::from()` ;
   - `DateTimeInterface` → date analysée / timestamp Unix ;
   - scalaire → typage coercitif de PHP ;
4. affecte la valeur via la réflexion (donc les propriétés `readonly` et à visibilité asymétrique fonctionnent aussi).

## Règles de conception (à connaître)

- **Propriétés publiques uniquement.** Les propriétés privées/protégées sont ignorées par design.
- **Récursif.** Objets imbriqués et tableaux d'objets sont hydratés en profondeur.
- **Types union & nullabilité respectés.** Un membre scalaire d'une union « l'emporte » sur une conversion de classe ambiguë (voir [Types → unions](types.md#unions--nullabilité)).
- **Échec explicite.** Une donnée invalide lève une unique [`HydrationException`](errors.md) — jamais de valeur erronée silencieuse.
- **Mis en cache.** Un [plan d'hydratation](performance.md) par classe est calculé une fois et réutilisé pour chaque objet.

## Un exemple plus complet

```php
use oihana\reflect\Reflection;
use oihana\reflect\attributes\HydrateKey;
use oihana\reflect\attributes\HydrateWith;
use oihana\reflect\attributes\Transient;

enum Role : string { case Admin = 'admin'; case Member = 'member'; }

class Tag    { public string $label = ''; }
class Member
{
    #[HydrateKey( '_key' )]
    public string $id = '';
    public Role   $role = Role::Member;
    public ?DateTimeImmutable $joinedAt = null;

    /** @var \Tag[] */
    public array $tags = [];

    #[Transient]                 // calculé ; jamais lu depuis les données ni sérialisé
    public int $score = 0;
}

$member = new Reflection()->hydrate(
[
    '_key'     => 'u-1',
    'role'     => 'admin',
    'joinedAt' => '2024-03-01T12:00:00+00:00',
    'tags'     => [ [ 'label' => 'php' ], [ 'label' => 'arango' ] ],
    'score'    => 999,          // ignoré (Transient)
] , Member::class );

$member->id;          // 'u-1'
$member->role;        // Role::Admin
$member->joinedAt;    // DateTimeImmutable
$member->tags[0];     // Tag { label: 'php' }
$member->score;       // 0  (non écrasé)
```

## Suite

- [Attributs](attributes.md) — mapping déclaratif.
- [Types](types.md) — enums, dates, unions, coercition, `readonly`, constructeurs.
- [Erreurs](errors.md) — `HydrationException`.
- [Performance](performance.md) — le cache de plan.
