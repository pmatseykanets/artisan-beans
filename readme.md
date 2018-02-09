# artisan-beans

[![Laravel 5.x](https://img.shields.io/badge/Laravel-5.x-orange.svg)](http://laravel.com)
[![StyleCI](https://styleci.io/repos/41767069/shield)](https://styleci.io/repos/41767069)
[![Latest Stable Version](https://poser.pugx.org/pmatseykanets/artisan-beans/v/stable)](https://packagist.org/packages/pmatseykanets/artisan-beans)
[![Total Downloads](https://img.shields.io/packagist/dt/pmatseykanets/artisan-beans.svg?style=flat-square)](https://packagist.org/packages/pmatseykanets/artisan-beans)
[![License](https://poser.pugx.org/pmatseykanets/artisan-beans/license)](https://packagist.org/packages/pmatseykanets/artisan-beans)

This package brings a set of artisan commands that allows you manage your [Beanstalkd](https://kr.github.io/beanstalkd/) job queues.

## Contents

- [Installation](#installation)
- [Usage](#usage)
- [Security](#security)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via composer:

**Laravel 5.6**

```bash
$ composer require pmatseykanets/artisan-beans
```

**Laravel <= 5.5**

```bash
$ composer require pmatseykanets/artisan-beans:1.0.0
```

If you're using Laravel < 5.5 or if you have package auto-discovery turned off you have to manually register the service provider:

```php
// config/app.php
'providers' => [
    ...
    Pvm\ArtisanBeans\ArtisanBeansServiceProvider::class,
],
```

## Usage

You're good to go. Run `php artisan` and you'll see new commands under the `beans` namespace.

```bash
$ php artisan
 beans
  beans:bury          Bury a job
  beans:delete        Delete a job
  beans:export        Export jobs
  beans:import        Import jobs
  beans:kick          Kick a job
  beans:move          Move jobs between tubes
  beans:pause         Pause the tube
  beans:peek          Peek a job
  beans:purge         Purge jobs from the tube
  beans:put           Put a job into the tube
  beans:server        Show server statistics
  beans:tube          Show tube statistics
  beans:unpause       Unpause the tube
```

## Security

If you discover any security related issues, please email pmatseykanets@gmail.com instead of using the issue tracker.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Peter Matseykanets](https://github.com/pmatseykanets)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
