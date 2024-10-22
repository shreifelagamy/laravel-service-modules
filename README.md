# Service Modules Generator for Laravel

![Laravel Service Modules](images/laravel-service-modules.png)

A Laravel package to easily generate service modules for your Laravel applications.

## Understanding the Architecture

To gain a deeper understanding of the architecture and how to effectively use service modules in Laravel, you can read this Medium article:

[Simplify External API Integrations in Laravel Using Service Modules](https://medium.com/@theshreif/simplify-external-api-integrations-in-laravel-using-service-modules-56493a651a0e)

This article provides insights into the benefits and implementation details of using service modules for external API integrations in Laravel applications.

## Installation

You can install the package via composer:

```bash
composer require shreifelagamy/laravel-service-modules
```

## Usage

```bash
php artisan service:generate UserService
```

This will create a new service class in the `app/Services` directory.

During the generation process, you will be prompted to:
1. Generate an exception for the service
2. Add methods to the repository
3. Include Data Transfer Objects (DTOs) for the service

These prompts allow you to customize the service module according to your needs.

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
├── Exceptions/ (optional)
│ └── UserServiceException.php
└── DTOs/ (optional)
  └── UserData.php
```

## Configuration

The package comes with a default configuration file that you can publish to customize the behavior:

```bash
php artisan vendor:publish --tag=laravel-service-modules-config
```

This will create a `config/laravel-service-modules.php` file where you can modify the default settings.

## Customization

You can control the directory name for generated services through the config file. This allows you to customize where your service modules are created within your Laravel application.

## TODO

We're constantly working to improve this package. Here are some features we're planning to add in the future:

1. Support auto-creating PHPDoc for facades to include method definitions.
2. Add more customization options for DTOs.

Stay tuned for these upcoming enhancements!

## License

The Laravel Services Generator is open-sourced software licensed under the MIT license.



