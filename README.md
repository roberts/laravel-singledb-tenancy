# Laravel package for single database multi-tenancy

[![Latest Version on Packagist](https://img.shields.io/packagist/v/roberts/laravel-singledb-tenancy.svg?style=flat-square)](https://packagist.org/packages/roberts/laravel-singledb-tenancy)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/roberts/laravel-singledb-tenancy/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/roberts/laravel-singledb-tenancy/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/roberts/laravel-singledb-tenancy/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/roberts/laravel-singledb-tenancy/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/roberts/laravel-singledb-tenancy.svg?style=flat-square)](https://packagist.org/packages/roberts/laravel-singledb-tenancy)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require roberts/laravel-singledb-tenancy
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-singledb-tenancy-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-singledb-tenancy-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-singledb-tenancy-views"
```

## Usage

```php
$laravelSingledbTenancy = new Roberts\LaravelSingledbTenancy();
echo $laravelSingledbTenancy->echoPhrase('Hello, Roberts!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Drew Roberts](https://github.com/drewroberts)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
