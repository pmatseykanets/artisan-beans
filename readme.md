# artisan-beans

[![Laravel 5.1](https://img.shields.io/badge/Laravel-5.1-orange.svg)](http://laravel.com)
[![StyleCI](https://styleci.io/repos/41767069/shield)](https://styleci.io/repos/41767069)
[![Latest Stable Version](https://poser.pugx.org/pmatseykanets/artisan-beans/v/stable)](https://packagist.org/packages/pmatseykanets/artisan-beans)
[![License](https://poser.pugx.org/pmatseykanets/artisan-beans/license)](https://packagist.org/packages/pmatseykanets/artisan-beans)

This package contains a set of artisan commands that allows you manage your [Beanstalkd](https://kr.github.io/beanstalkd/) job queue server instance(s).

## Installation

### 1. Install through composer

```bash
$ composer require pmatseykanets/artisan-beans
```

### 2. Add a ServiceProvider

Open `app/config/app.php`, and add a new item to the providers array.

```php
Pvm\ArtisanBeans\ArtisanBeansServiceProvider::class
```
### 3. Run artisan

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
  beans:unpause       Upause the tube
```

## Roadmap

* Add usage examples to this readme

## License

The artisan-beans is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

