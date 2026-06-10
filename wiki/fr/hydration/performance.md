# Hydratation — performance

[← Retour au sommaire](../README.md) · [← Vue d'ensemble](README.md)

## Le plan d'hydratation par classe

À la première hydratation d'une classe, `Reflection` construit un **plan d'hydratation** et le met en cache. Le plan contient tout ce qui ne dépend que de la définition de la classe — jamais des données :

- la **clé source** de chaque propriété (`#[HydrateKey]` résolu) ;
- les **types** déclarés et les noms de types natifs ;
- les classes `#[HydrateWith]` / `#[HydrateAs]` résolues ;
- la classe d'élément `@var` (la regex du doc-comment est exécutée **une seule fois**) ;
- la stratégie de constructeur (`new` vs `newInstanceWithoutConstructor`) ;
- les drapeaux de visibilité, et quelles propriétés sont `#[Transient]`.

Chaque objet suivant de la même classe réutilise ce plan au lieu de relire attributs, doc-comments et métadonnées de constructeur.

## Pourquoi c'est important

Sur une charge qui hydrate de nombreux objets de la même classe (imbriquée) — ex. un grand jeu de résultats — cela supprime le travail de réflexion répété. Mesure sur un document imbriqué représentatif, hydraté 10 000 fois :

| | Temps (10k docs) | Par doc |
|---|---|---|
| Sans cache de plan | ~800 ms | ~80 µs |
| Avec cache de plan | ~517 ms | ~52 µs |

Soit environ **−35 %**. Plus l'imbrication est profonde, plus le gain est grand (chaque classe imbriquée en profite aussi).

## Comment en profiter

Le cache vit sur l'**instance** `Reflection`. Pour le réutiliser sur de nombreuses hydratations, réutilisez la même instance :

```php
use oihana\reflect\Reflection;

$reflection = new Reflection();              // construit une fois

foreach ( $rows as $row )
{
    $items[] = $reflection->hydrate( $row , Product::class ); // plan réutilisé
}
```

Lorsque vous hydratez via [`ReflectionTrait`](../reflection.md), le trait détient déjà une instance `Reflection` partagée, donc les hydratations imbriquées et répétées en profitent automatiquement.

## Caractéristiques

- **En mémoire**, sans store externe (pas d'APCu/Redis/etc.).
- **Borné** par le nombre de classes distinctes hydratées (une entrée par classe, pas par objet) — typiquement quelques Ko au total.
- **Aucune éviction nécessaire** : PHP libère tout en fin de requête/process. Le comportement est identique avec ou sans le cache — il ne fait que supprimer le travail redondant.

## Vider le cache

Généralement inutile, mais dans les tests ou les workers longue-durée (RoadRunner, Swoole, queues) vous pouvez supprimer toutes les entrées mises en cache — à la fois les instances `ReflectionClass` et les plans d'hydratation — avec :

```php
$reflection->clearCache(); // les caches sont reconstruits de façon transparente au prochain appel
```
