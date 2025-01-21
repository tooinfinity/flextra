# Flextra: Minimal Laravel Authentication with Inertia, React, Vue, Svelte, and nWidart Laravel Modules

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tooinfinity/flextra.svg?style=flat-square)](https://packagist.org/packages/tooinfinity/flextra)  [![Total Downloads](https://img.shields.io/packagist/dt/tooinfinity/flextra.svg?style=flat-square)](https://packagist.org/packages/tooinfinity/flextra)  ![GitHub Actions](https://github.com/tooinfinity/flextra/actions/workflows/tests.yml/badge.svg)

**Flextra** is a versatile Laravel package designed to simplify authentication scaffolding. It seamlessly integrates **InertiaJS** (React, Vue, Svelte), **nWidart Laravel Modules**, and **Tailwind CSS**, enabling you to build modular, scalable, and modern web applications effortlessly.

The name **Flextra** combines "Flex" (flexibility) and "Extra" (enhanced features), embodying the package's mission to deliver flexible and robust solutions for Laravel development.

---

## Features

- **Authentication Scaffolding:** Provides a lightweight and modular authentication system for Laravel projects.
- **InertiaJS Integration:** Supports frontends built with React, Vue, and Svelte via InertiaJS.
- **nWidart Laravel Modules:** Leverages modular architecture to enable scalable and maintainable applications.
- **Tailwind CSS:** Styled with modern and responsive design principles using Tailwind.

---

## Installation

Install the package using Composer : 

```bash
composer require tooinfinity/flextra:dev-main
```
## Usage
Supported stacks: **react**, **vue**

Options: **--typescript**, **--ssr**, **--pest**

```php
php artisan flextra:install [stack] --options
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
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
