# Laravel Service Modules Generator

![Laravel Service Modules](images/laravel-service-modules.png)

A Laravel package to easily generate service modules for your Laravel applications.

## Installation

You can install the package via composer:

```bash
composer require shreifelagamy/laravel-services
```

## Usage


```bash
php artisan service:generate UserService
```

This will create a new service class in the `app/Services` directory.

The generated service structure will look like this:

```
app/Services/UserService/
├── Providers/
│ └── UserServiceProvider.php
├── Repositories/
│ ├── UserServiceInterface.php
│ └── UserServiceRepository.php
├── Facades/
│ └── UserService.php
└── Exceptions/ (optional)
  └── UserServiceException.php
```

## Configuration

The package comes with a default configuration file that you can publish to customize the behavior:

```bash
php artisan vendor:publish --tag=laravel-services-config
```

This will create a `config/laravel-services.php` file where you can modify the default settings.

## Customization

You can customize the service class by creating a new class in the `app/Services` directory.

## License

The Laravel Services Generator is open-sourced software licensed under the MIT license.



