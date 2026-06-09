# Hydratation — erreurs

[← Retour au sommaire](../README.md) · [← Vue d'ensemble](README.md)

Tout échec d'hydratation est signalé via un type unique et catchable : **`oihana\reflect\exceptions\HydrationException`**.

## Ce qu'elle unifie

`HydrationException` est levée pour :

- une **classe inexistante** (`hydrate($data, 'NoSuchClass')`) ;
- une propriété **non-nullable** mise à `null` ;
- une **valeur d'enum backed invalide** (enveloppe le `ValueError` sous-jacent) ;
- un **enum pur (non-backed)** hydraté depuis un scalaire ;
- un **scalaire non coercible** (ex. `'abc'` → `int`, enveloppe le `TypeError` sous-jacent) ;
- une **date non analysable** ;
- tout autre échec survenant lors de l'affectation d'une propriété.

## API

```php
namespace oihana\reflect\exceptions;

class HydrationException extends \InvalidArgumentException
{
    public function getClassName(): ?string;     // FQCN en cours d'hydratation (si connu)
    public function getPropertyName(): ?string;  // propriété en échec (le cas échéant)
    // getPrevious() retourne l'erreur bas niveau enveloppée (ValueError, TypeError, ...)
}
```

Elle **étend `InvalidArgumentException`**, donc le code existant en `catch (InvalidArgumentException)` ou `catch (Throwable)` continue de fonctionner sans changement.

## Gérer un enregistrement isolé

```php
use oihana\reflect\Reflection;
use oihana\reflect\exceptions\HydrationException;

try
{
    $product = new Reflection()->hydrate( $row , Product::class );
}
catch ( HydrationException $e )
{
    $logger->warning( sprintf(
        'Échec d\'hydratation pour %s::$%s — %s',
        $e->getClassName(),
        $e->getPropertyName(),
        $e->getMessage()
    ) );
    // inspecter la cause racine :
    $cause = $e->getPrevious(); // ex. ValueError / TypeError, ou null
}
```

## Ignorer les enregistrements invalides dans un lot / flux

Le type unique catchable facilite la résilience lors de l'hydratation de nombreux documents (ex. un jeu de résultats de base) :

```php
$valid = [];

foreach ( $rows as $row )
{
    try
    {
        $valid[] = new Reflection()->hydrate( $row , Product::class );
    }
    catch ( HydrationException $e )
    {
        $logger->notice( 'Ligne invalide ignorée : ' . $e->getMessage() );
    }
}
```

## Voir aussi

- [Types](types.md) — quelles valeurs sont valides pour chaque type déclaré.
