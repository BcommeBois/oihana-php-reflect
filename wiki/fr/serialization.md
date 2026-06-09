# Sérialisation

[← Retour au sommaire](README.md)

La bibliothèque fournit deux sérialiseurs légers qui appliquent des **options de sérialisation temporaires et délimitées** pendant l'encodage, plus le contexte partagé qui les transporte.

## JsonSerializer

`oihana\reflect\utils\JsonSerializer` enveloppe `json_encode()` et applique les options pour la durée de l'appel (puis restaure le contexte précédent) :

```php
use oihana\core\options\ArrayOption;
use oihana\reflect\utils\JsonSerializer;

echo JsonSerializer::encode( [ $person1, $person2 ] , JSON_PRETTY_PRINT , [ ArrayOption::REDUCE => true ] );
```

- `encode( mixed $data , int $jsonFlags = 0 , array $options = [] ) : string`
- `getOptions() : array` — les options temporaires courantes.

Les options sont globales **uniquement pour la durée de l'appel `encode()`**, et réinitialisées ensuite (même en cas d'erreur). Pratique quand de nombreux objets doivent partager des règles de formatage cohérentes pendant un seul encodage (ex. sortie JSON-LD).

## CborSerializer

`oihana\reflect\utils\CborSerializer` fait de même pour le CBOR, en enveloppant `oihana\core\cbor\cbor_encode()` :

```php
use oihana\reflect\utils\CborSerializer;

$bytes = CborSerializer::encode( $data , [ ArrayOption::REDUCE => true ] );
```

- `encode( mixed $data , array $options = [] , ?Closure $replacer = null ) : string`

Un callback `$replacer` optionnel `fn( $key , $value )` peut transformer chaque valeur encodée.

## SerializationContext

`oihana\reflect\utils\SerializationContext` est la source de vérité unique des options transitoires partagées entre sérialiseurs et objets métier pendant un encodage :

```php
use oihana\reflect\utils\SerializationContext;

SerializationContext::getOptions();        // lire les options actives (ex. dans jsonSerialize())
SerializationContext::setOptions( $opts ); // définir (points d'entrée des sérialiseurs uniquement)
SerializationContext::reset( $previous );  // restaurer (dans un bloc finally)
```

En général vous ne l'appelez pas directement — les sérialiseurs la gèrent via try/finally. Les objets métier lisent `SerializationContext::getOptions()` dans leur `jsonSerialize()` pour respecter les options actives.

> Des helpers de décodage (`JsonSerializer::decode()`, `CborSerializer::decode()`) sont prévus. Pour retransformer aujourd'hui des données décodées en objets typés, décodez avec `json_decode($json, true)` / `cbor_decode($bytes)` et passez le tableau à [`Reflection::hydrate()`](hydration/README.md).
