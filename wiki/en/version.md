# Version

[← Back to index](README.md)

`oihana\reflect\Version` is a compact value object that packs four components — **major**, **minor**, **build**, **revision** — into a single 32-bit integer (16 bits for major/build via the layout, with getters/setters exposing each part).

```php
use oihana\reflect\Version;

$v = new Version( 1 , 2 , 3 , 4 );

$v->major;    // 1
$v->minor;    // 2
$v->build;    // 3
$v->revision; // 4
```

## String & integer forms

```php
(string) $v;        // '1.2.3.4'  (configurable string output)
$v->valueOf();      // the packed 32-bit integer value
```

## Parsing

`fromString()` parses a formatted version string and returns its **normalized string** form (or `null` when parsing fails):

```php
Version::fromString( '1.2.3.4' );        // '1.2.3.4'
Version::fromString( '1-2-3' , '-' );    // parsed with a custom separator
Version::fromString( '' );               // null
```

## Comparison

```php
$v->equals( new Version( 1 , 2 , 3 , 4 ) ); // true
```

`equals()` compares against another `Version` (by packed value).
