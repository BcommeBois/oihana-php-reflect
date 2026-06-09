# ConstantsTrait

[← Back to index](README.md)

`oihana\reflect\traits\ConstantsTrait` turns a class full of `public const` values into a lightweight **enumeration** with lookup, listing and validation helpers — without the constraints of native enums. Methods are **static** and the constant list is cached per class.

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

## Listing

```php
Color::getAll();           // [ 'RED' => 'red', 'GREEN' => 'green', 'BLUE' => 'blue' ]
Color::getConstantKeys();  // [ 'RED', 'GREEN', 'BLUE' ]
Color::getConstantValues();// [ 'red', 'green', 'blue' ]
Color::enums();            // [ 'red', 'green', 'blue' ] (sorted; pass SORT_* flags)
```

## Lookup

```php
Color::get( 'red' );              // 'red'   (the value if it exists, else the default)
Color::get( 'pink' , 'unknown' ); // 'unknown'
Color::getConstant( 'red' );      // 'RED'   (the constant *name* for a value)
```

`getConstant()` accepts an optional separator (to look up compound values) and a case-insensitive flag:

```php
Color::getConstant( 'RED' , null , caseInsensitive: true ); // 'RED'
```

## Membership & validation

```php
Color::includes( 'red' );             // true
Color::includes( 'pink' );            // false
Color::includes( 'red,blue' , separator: ',' ); // true if every part is a member

Color::validate( 'red' );             // void — OK
Color::validate( 'pink' );            // throws oihana\reflect\exceptions\ConstantException
```

`validate()` throws a `ConstantException` when the value is not a valid constant; `includes()` returns a boolean. Both accept a `strict` flag and an optional `separator` for multi-value checks.

## Cache

The constant list is memoized per class. Reset it (e.g. in tests) with:

```php
Color::resetCaches();
```

## See also

- [Helpers → useConstantsTrait](helpers.md#useconstantstrait) — detect whether a class uses this trait.
