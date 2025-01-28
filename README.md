# Flextra: Minimal Laravel Authentication Breeze support  nWidart Laravel Modules

Laravel Authentication Breeze support with nWidart Laravel Modules with Frontend Frameworks (React, Vue, Svelte, Blade) and Tailwind CSS.

## Project that inspired and used to build this package
- Laravel [Breeze](https://github.com/laravel/breeze)
- Laravel Modules [nWidart/laravel-modules](https://github.com/nWidart/laravel-modules)
- [xavi7th/laravel-inertiajs-tailwindcss-starter](https://github.com/xavi7th/laravel-inertia-svelte-starter-template/tree/main)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tooinfinity/flextra.svg?style=flat-square)](https://packagist.org/packages/tooinfinity/flextra)  [![Total Downloads](https://img.shields.io/packagist/dt/tooinfinity/flextra.svg?style=flat-square)](https://packagist.org/packages/tooinfinity/flextra)  ![GitHub Actions](https://github.com/tooinfinity/flextra/actions/workflows/tests.yml/badge.svg)  [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

**Flextra** is a versatile Laravel package designed to simplify authentication scaffolding. It seamlessly integrates **InertiaJS** (React, Vue, Svelte), Blade, **nWidart Laravel Modules**, and **Tailwind CSS**, enabling you to build modular, scalable, and modern web applications effortlessly.

The name **Flextra** combines "Flex" (flexibility) and "Extra" (enhanced features), embodying the package's mission to deliver flexible and robust solutions for Laravel development.

---

## Features

- **Authentication Scaffolding:** Provides a lightweight and modular authentication system for Laravel projects.
- **InertiaJS Integration:** Supports frontends built with React, Vue, and Svelte via InertiaJS.
- **Blade Support:** Offers traditional Blade views for projects that do not require frontend frameworks.
- **nWidart Laravel Modules:** Leverages modular architecture to enable scalable and maintainable applications.
- **Tailwind CSS:** Styled with modern and responsive design principles using Tailwind.

---

## Installation

Install the package using Composer : 

```bash
composer require tooinfinity/flextra
```
## Usage

this Package is installed and setup laravel modules and breeze authentication with stack you prefer and Tailwind CSS automatically.
by default the name of the module is `Auth` but you can change it by passing the name of the module as an options like this.
    
```php
php artisan flextra:install [stack] --module=Authentications [--other-options]
```

Supported stacks: **react**, **vue**, **svelte**, **blade**

Options: **--typescript**, **--ssr**, **--pest**

```php
php artisan flextra:install [stack] --options
```
### Example

React Stack

```php
php artisan flextra:install react
```
Vue Stack with options

```php
php artisan flextra:install vue --typescript --ssr --pest
```

```php
php artisan flextra:install vue --module=Authentications  --typescript --ssr --pest
```

### Testing is not implement yet

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

-   [TouwfiQ Meghlaoui](https://github.com/tooinfinity)
-   [Nicolas Widart](https://github.com/nWidart)
-   [Taylor Otwell](https://github.com/taylorotwell)
-   [Akhile E. Daniel](https://github.com/xavi7th)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
