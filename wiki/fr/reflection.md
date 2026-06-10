# API Reflection

[← Retour au sommaire](README.md)

`oihana\reflect\Reflection` est une surcouche haut niveau et mise en cache de l'API Reflection de PHP. `oihana\reflect\traits\ReflectionTrait` expose les mêmes helpers (plus `toArray()`) sur vos propres classes.

```php
use oihana\reflect\Reflection;

$reflection = new Reflection(); // met en cache les ReflectionClass et les plans d'hydratation
```

## Classes, constantes, méthodes, propriétés

```php
$reflection->constants( MyClass::class );                 // ['FOO' => 'bar', ...] (publiques par défaut)
$reflection->methods( MyClass::class );                   // ReflectionMethod[] (publiques par défaut)
$reflection->properties( MyClass::class );                // ReflectionProperty[] (publiques par défaut)
$reflection->reflection( MyClass::class );                // la ReflectionClass mise en cache
```

Les filtres de visibilité utilisent les masques natifs, ex. `$reflection->properties( $c , ReflectionProperty::IS_PROTECTED )`.

## Introspection classe / propriété

```php
$reflection->hasMethod( MyClass::class , 'doThing' );     // bool (ne lève jamais)
$reflection->hasProperty( MyClass::class , 'name' );      // bool
$reflection->propertyType( MyClass::class , 'name' );     // 'string' | 'int|string' | null
$reflection->namespace( MyClass::class );                 // 'App\Model' ('' si global)
$reflection->shortName( MyClass::class );                 // 'MyClass'
```

`propertyType()` rend les types union sous la forme `A|B` et les types intersection `A&B` ; retourne `null` pour une propriété non typée ou absente.

## Lire les attributs

`classAttributes()`, `propertyAttributes()` et `methodAttributes()` retournent les attributs **instanciés** d'une cible, avec un filtre optionnel par classe d'attribut :

```php
use oihana\reflect\attributes\HydrateKey;

$reflection->classAttributes( User::class );                       // tous les attributs de classe (instances)
$reflection->propertyAttributes( User::class , 'name' , HydrateKey::class )[0]->key; // 'user_name'
$reflection->methodAttributes( Controller::class , 'index' , Route::class );
```

Chaque élément retourné est déjà `newInstance()`-é. Un filtre sans correspondance retourne un tableau vide.

## Paramètres de méthode

```php
$reflection->parameters( MyClass::class , 'demo' );                 // ReflectionParameter[]
$reflection->hasParameter( MyClass::class , 'demo' , 'name' );      // bool
$reflection->parameterType( MyClass::class , 'demo' , 'name' );     // 'string' | 'int|string' | null
$reflection->parameterDefaultValue( MyClass::class , 'demo' , 'x' );// mixed|null
$reflection->isParameterNullable( MyClass::class , 'demo' , 'x' );  // bool
$reflection->isParameterOptional( MyClass::class , 'demo' , 'x' );  // bool
$reflection->isParameterVariadic( MyClass::class , 'demo' , 'x' );  // bool
```

## Décrire n'importe quel callable

`describeCallableParameters()` fonctionne sur closures, noms de fonctions, chaînes `Class::method`, tableaux `[ $obj, 'method' ]` et objets invocables :

```php
$reflection->describeCallableParameters( fn( string $name , int $age = 42 ) => '' );
// [
//   [ 'name' => 'name', 'type' => 'string', 'optional' => false, 'nullable' => false, 'variadic' => false ],
//   [ 'name' => 'age',  'type' => 'int',    'optional' => true,  'nullable' => false, 'variadic' => false, 'default' => 42 ],
// ]
```

Les clés du descripteur sont des constantes nommées dans `oihana\reflect\enums\CallableParameter` (`NAME`, `TYPE`, `OPTIONAL`, `NULLABLE`, `VARIADIC`, `DEFAULT`).

## Hydratation

`hydrate()` est documentée dans sa propre section — voir [Hydratation](hydration/README.md).

```php
$reflection->hydrate( $data , MyClass::class );
```

## `ReflectionTrait`

Ajoutez le trait pour exposer les helpers sur les instances, plus `toArray()` :

```php
use oihana\reflect\traits\ReflectionTrait;

class User
{
    use ReflectionTrait;
    public string  $name = '';
    public ?string $bio  = null;
    public int     $age  = 0;
}
```

Wrappers : `getConstants()`, `getPublicProperties()`, `getShortName()`, `getNamespace()`, `getMethodParameters()`, `getParameterType()`, `getParameterDefaultValue()`, `hasParameter()`, `hasMethod()`, `hasProperty()`, `getPropertyType()`, `getClassAttributes()`, `getPropertyAttributes()`, `getMethodAttributes()`, `isParameterNullable/Optional/Variadic()`, et `hydrate()`.

### `toArray()`

Sérialise les propriétés **publiques et initialisées** en tableau, avec les options de `oihana\core\options\ArrayOption` :

```php
use oihana\core\options\ArrayOption;

new User()->toArray(
[
    ArrayOption::REDUCE     => true,                  // retirer les valeurs null
    ArrayOption::INCLUDE    => [ 'name', 'age' ],     // liste blanche
    ArrayOption::EXCLUDE    => [ 'bio' ],             // liste noire
    ArrayOption::BEFORE     => [ '_type' => 'User' ], // préfixer
    ArrayOption::AFTER      => [],                     // suffixer
    ArrayOption::FIRST_KEYS => [ '_type', 'name' ],   // forcer l'ordre
    ArrayOption::SORT       => true,                   // trier les clés restantes
    ArrayOption::DEFAULTS   => [ 'bio' => 'n/a' ],     // défauts pour manquant/null
] );
```

Les propriétés marquées [`#[Transient]` / `#[HydrateIgnore]`](hydration/attributes.md#transient--hydrateignore) ne sont jamais émises.

Deux options propres au package (depuis `oihana\reflect\enums\SerializeOption`) rendent `toArray()` symétrique avec `hydrate()` :

```php
use oihana\reflect\enums\SerializeOption;

// Les valeurs DateTimeInterface sont sérialisées en ISO 8601 par défaut ; surchargez le format :
$user->toArray( [ SerializeOption::DATE_FORMAT => 'Y-m-d' ] );

// Opt-in : émettre chaque propriété sous sa clé source #[HydrateKey] au lieu de son nom :
$user->toArray( [ SerializeOption::USE_HYDRATE_KEYS => true ] ); // 'user_name' au lieu de 'name'
```

## Voir aussi

- [Hydratation](hydration/README.md) · [JSON Schema](json-schema.md) · [Sérialisation](serialization.md)
