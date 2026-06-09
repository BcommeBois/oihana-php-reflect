# ConstantsTrait

[← Retour au sommaire](README.md)

`oihana\reflect\traits\ConstantsTrait` transforme une classe pleine de valeurs `public const` en une **énumération** légère avec recherche, listage et validation — sans les contraintes des enums natifs. Les méthodes sont **statiques** et la liste des constantes est mise en cache par classe.

```php
use oihana\reflect\traits\ConstantsTrait;

class Color
{
    use ConstantsTrait;

    public const string RED   = 'red';
    public const string GREEN = 'green';
    public const string BLUE  = 'blue';
}
```

## Listage

```php
Color::getAll();           // [ 'RED' => 'red', 'GREEN' => 'green', 'BLUE' => 'blue' ]
Color::getConstantKeys();  // [ 'RED', 'GREEN', 'BLUE' ]
Color::getConstantValues();// [ 'red', 'green', 'blue' ]
Color::enums();            // [ 'red', 'green', 'blue' ] (trié ; passez des flags SORT_*)
```

## Recherche

```php
Color::get( 'red' );              // 'red'   (la valeur si elle existe, sinon le défaut)
Color::get( 'pink' , 'unknown' ); // 'unknown'
Color::getConstant( 'red' );      // 'RED'   (le *nom* de constante pour une valeur)
```

`getConstant()` accepte un séparateur optionnel (pour des valeurs composées) et un drapeau insensible à la casse :

```php
Color::getConstant( 'RED' , null , caseInsensitive: true ); // 'RED'
```

## Appartenance & validation

```php
Color::includes( 'red' );             // true
Color::includes( 'pink' );            // false
Color::includes( 'red,blue' , separator: ',' ); // true si chaque partie est membre

Color::validate( 'red' );             // void — OK
Color::validate( 'pink' );            // lève oihana\reflect\exceptions\ConstantException
```

`validate()` lève une `ConstantException` quand la valeur n'est pas une constante valide ; `includes()` renvoie un booléen. Les deux acceptent un drapeau `strict` et un `separator` optionnel pour les vérifications multi-valeurs.

## Cache

La liste des constantes est mémoïsée par classe. Réinitialisez-la (ex. dans les tests) avec :

```php
Color::resetCaches();
```

## Voir aussi

- [Helpers → useConstantsTrait](helpers.md#useconstantstrait) — détecter si une classe utilise ce trait.
