# Laravel Struct

[![Latest Stable Version](https://poser.pugx.org/proai/laravel-struct/v/stable)](https://packagist.org/packages/proai/laravel-struct) [![Total Downloads](https://poser.pugx.org/proai/laravel-struct/downloads)](https://packagist.org/packages/proai/laravel-struct) [![Latest Unstable Version](https://poser.pugx.org/proai/laravel-struct/v/unstable)](https://packagist.org/packages/proai/laravel-struct) [![License](https://poser.pugx.org/proai/laravel-struct/license)](https://packagist.org/packages/proai/laravel-struct)

A struct is a collection of typed variables. Structs are a well known datatype in other programming languages, but unfortunately not natively part of PHP yet. This package aims to bring structs to PHP and in particular to Laravel.

## Installation

You can install the package via composer:

```bash
composer require proai/laravel-struct
```

Please note that you need at least **PHP 7.4** and **Laravel 8** for this package.

## Usage

The package uses named properties, which were introduced in PHP 7.4, to define a struct:

```php
use App\Structs\GeoLocation;
use ProAI\Struct\Struct;

class Address extends Struct
{
    public string $street;

    public string $city;

    public GeoLocation $geo_location;
}
```

You can use all primitive types like `string`, `bool`, `float`, `int`, but also you can type a property as an object. The object can also be another struct, which enables you to nest structs (like `GeoLocation` above).

Structs are instantiated by using an array of values:

```php
$address = new Address([
    'street' => 'Baker Street',
    'city' => 'London',
    'geo_location' => [
        'latitude' => 51.52,
        'longitude' => -0.1566,
    ],
]);
```

Properties that are typed as objects will be converted to these objects on instantiation. Thus in the example above an object of `App\Structs\GeoLocation` will be created for the `$geo_location` property.

Properties can be accessed normally:

```php
$address->street
=> "Baker Street"

$address->country
=> App\Structs\GeoLocation { ... }
```

_Hint: Snake cased properties are used to mimic the behaviour of Eloquent attributes._

### Attribute Casting

You can use attribute casting with structs in your Eloquent models:

```php
use App\Structs\Address;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $casts = [
        'address' => Address::class,
    ];
}
```

In order to make this work you need to define a `json` column of the specified key, so in this case `address`.

Alternatively you can use the composed struct caster by adding the argument `composed` to compose a struct from multiple columns:

```php
use App\Structs\Address;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $casts = [
        'address' => Address::class.':composed',
    ];
}
```

The column names must start with the specified key followed by an underscore and the property name. This also works with nested structs. For the example above we would need the following columns:

```
address_street
address_city
address_geo_location_latitude
address_geo_location_longitude
```

Finally you can also write your own custom caster by overwriting the `castUsing` method of the struct like described in the Laravel docs.

### Collections

Sometimes you need an array of structs. For this purpose you can define a struct collection. The struct collection class is inherited from the Laravel collection class, so you can use all methods of a Laravel collection.

```php
use App\Structs\Address;
use ProAI\Struct\Collection;

class AddressCollection extends Collection
{
    public $type = Address::class;
}
```

By the way, attribute casting also works with struct collections for `json` columns.

## Support

Bugs and feature requests are tracked on [GitHub](https://github.com/proai/laravel-struct/issues).

## License

This package is released under the [MIT License](LICENSE).
